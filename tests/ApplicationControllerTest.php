<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\{Client, Application};
use OpenApi\Attributes as OA;

class ApplicationControllerTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private $browser;

    protected function setUp(): void
    {
        $this->browser = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->truncateTables();
    }

    public function testCreateApplication(): void
    {
        $client = $this->createClientEntity();

        $this->browser->jsonRequest('POST', '/applications', [
            'clientId' => $client->getId(),
            'term' => 30,
            'amount' => 3000.00,
            'currency' => 'EUR',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $data = json_decode($this->browser->getResponse()->getContent(), true);

        self::assertSame(30, $data['term']);
        self::assertEquals(3000.00, $data['amount']);
        self::assertSame('EUR', $data['currency']);
        self::assertArrayHasKey('id', $data);
    }

    public function testCreateApplicationWithMissingClientId(): void
    {
        $this->browser->jsonRequest('POST', '/applications', [
            'term' => 30,
            'amount' => 3000.00,
            'currency' => 'EUR',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $data = json_decode($this->browser->getResponse()->getContent(), true);

        self::assertSame(['Invalid clientId'], $data['errors']);
    }

    public function testCreateApplicationWithInvalidClientId(): void
    {
        $this->browser->jsonRequest('POST', '/applications', [
            'clientId' => 999999,
            'term' => 30,
            'amount' => 3000.00,
            'currency' => 'EUR',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testCreateApplicationWithValidationErrors(): void
    {
        $client = $this->createClientEntity();

        $this->browser->jsonRequest('POST', '/applications', [
            'clientId' => $client->getId(),
            'term' => 9,
            'amount' => 99.99,
            'currency' => 'USD',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $data = json_decode($this->browser->getResponse()->getContent(), true);

        self::assertArrayHasKey('errors', $data);
    }

    public function testGetApplication(): void
    {
        $application = $this->createApplicationEntity();

        $this->browser->jsonRequest('GET', '/applications/' . $application->getId());

        self::assertResponseIsSuccessful();

        $data = json_decode($this->browser->getResponse()->getContent(), true);

        self::assertSame($application->getId(), $data['id']);
        self::assertSame(30, $data['term']);
        self::assertSame('EUR', $data['currency']);
    }

    public function testGetMissingApplicationReturnsNotFound(): void
    {
        $this->browser->jsonRequest('GET', '/applications/999999');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testGetApplicationsPaginatedList(): void
    {
        $this->createApplicationEntity();
        $this->createApplicationEntity(term: 20, amount: 1000.00);

        $this->browser->jsonRequest('GET', '/applications?page=1&limit=10');

        self::assertResponseIsSuccessful();

        $data = json_decode($this->browser->getResponse()->getContent(), true);

        self::assertNotEmpty($data);
    }

    public function testUpdateApplication(): void
    {
        $application = $this->createApplicationEntity();

        $this->browser->jsonRequest('PATCH', '/applications/' . $application->getId(), [
            'term' => 20,
            'amount' => 1500.00,
        ]);

        self::assertResponseIsSuccessful();

        $data = json_decode($this->browser->getResponse()->getContent(), true);

        self::assertSame(20, $data['term']);
        self::assertEquals(1500.00, $data['amount']);
        self::assertSame('EUR', $data['currency']);
    }

    public function testUpdateApplicationClient(): void
    {
        $oldClient = $this->createClientEntity('John', 'Doe', 'john@example.com', '+37101234567');
        $newClient = $this->createClientEntity('Jane', 'Doe', 'jane@example.com', '+37101234568');

        $application = $this->createApplicationEntity($oldClient);

        $this->browser->jsonRequest('PATCH', '/applications/' . $application->getId(), [
            'clientId' => $newClient->getId(),
        ]);

        self::assertResponseIsSuccessful();

        $this->entityManager->refresh($application);

        self::assertSame($newClient->getId(), $application->getClient()->getId());
    }

    public function testUpdateApplicationRejectsInvalidClientId(): void
    {
        $application = $this->createApplicationEntity();

        $this->browser->jsonRequest('PATCH', '/applications/' . $application->getId(), [
            'clientId' => 999999,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testUpdateApplicationRejectsUnwritableFields(): void
    {
        $application = $this->createApplicationEntity();

        $this->browser->jsonRequest('PATCH', '/applications/' . $application->getId(), [
            'id' => 123,
            'unknownField' => 'value',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        self::assertContains('id', $data['errors']['fields']);
        self::assertContains('unknownField', $data['errors']['fields']);
    }

    public function testDeleteApplication(): void
    {
        $application = $this->createApplicationEntity();
        $applicationId = $application->getId();

        $this->browser->jsonRequest('DELETE', '/applications/' . $applicationId);

        self::assertResponseIsSuccessful();
        self::assertNull($this->entityManager->getRepository(Application::class)->find($applicationId));
    }

    private function createClientEntity(string $firstName = 'John',
                                        string $lastName = 'Doe',
                                        string $email = 'john.doe@mail.com',
                                        string $phoneNumber = '+37101234567'): Client 
    {
        $client = new Client();
        $client->updateFields([
            'firstName' => $firstName,
            'lastName' => $lastName,
            'email' => $email,
            'phoneNumber' => $phoneNumber,
        ]);

        $this->entityManager->persist($client);
        $this->entityManager->flush();

        return $client;
    }

    private function createApplicationEntity(?Client $client = null, 
                                              int $term = 30,
                                              float $amount = 3000.00,
                                              string $currency = 'EUR'): Application {
        $client ??= $this->createClientEntity();

        $application = new Application();
        $application->updateFields([
            'client' => $client,
            'term' => $term,
            'amount' => $amount,
            'currency' => $currency,
        ]);

        $this->entityManager->persist($application);
        $this->entityManager->flush();

        return $application;
    }


    private function truncateTables(): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('TRUNCATE TABLE application RESTART IDENTITY CASCADE');
        $connection->executeStatement('TRUNCATE TABLE client RESTART IDENTITY CASCADE');
    }
}
