<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Client;

class ClientControllerTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private $browser;

    protected function setUp(): void
    {
        $this->browser = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->truncateTables();
    }

    private function truncateTables(): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('TRUNCATE TABLE application RESTART IDENTITY CASCADE');
        $connection->executeStatement('TRUNCATE TABLE client RESTART IDENTITY CASCADE');
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

    public function testCreateClient(): void
    {
        $this->browser->JsonRequest('POST', '/clients', [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'test@test.com',
            'phoneNumber' => '+37112345678'
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $responseData = json_decode($this->browser->getResponse()->getContent(), true);
        self::assertSame('John', $responseData['firstName']);
        self::assertSame('Doe', $responseData['lastName']);
        self::assertSame('test@test.com', $responseData['email']);
        self::assertSame('+37112345678', $responseData['phoneNumber']);
        self::assertArrayHasKey('id', $responseData);
    }

    public function testCreateClientWithInvalidJson(): void
    {
        $this->browser->Request('POST', '/clients', [], [], ['CONTENT_TYPE' => 'application/json']);
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $responseData = json_decode($this->browser->getResponse()->getContent(), true);
        self::assertSame(['Invalid JSON request data'], $responseData['errors']);
    }

    public function testCreateClientWithValidationErrors(): void
    {
        $this->browser->JsonRequest('POST', '/clients', [
            'firstName' => 'J',
            'lastName' => 'Doe1',
            'email' => 'invalid email',
            'phoneNumber' => '123'
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $responseData = json_decode($this->browser->getResponse()->getContent(), true);
        self::assertArrayHasKey('errors', $responseData);
    }

    public function testGetClient(): void
    {
        $client = $this->createClientEntity();
        $this->browser->jsonRequest('GET', '/clients/' . $client->getId());

        self::assertResponseIsSuccessful();
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        self::assertSame($client->getId(), $data['id']);
        self::assertSame('John', $data['firstName']);
    }

    public function testGetMissingClientReturnsNotFound(): void
    {
        $this->browser->jsonRequest('GET', '/clients/999999');
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testGetClientsPaginatedList(): void
    {
        $this->createClientEntity('John', 'Doe', 'john@example.com', '+37101234567');
        $this->createClientEntity('Jane', 'Doe', 'jane@example.com', '+37101234568');
        $this->browser->jsonRequest('GET', '/clients?page=1&limit=10');

        self::assertResponseIsSuccessful();

        $data = json_decode($this->browser->getResponse()->getContent(), true);

        self::assertNotEmpty($data);
    }

    public function testUpdateClient(): void
    {
        $client = $this->createClientEntity();
        $this->browser->jsonRequest('PATCH', '/clients/' . $client->getId(), [
            'firstName' => 'Jane',
            'email' => 'jane.doe@mail.com',
        ]);

        self::assertResponseIsSuccessful();

        $data = json_decode($this->browser->getResponse()->getContent(), true);

        self::assertSame('Jane', $data['firstName']);
        self::assertSame('jane.doe@mail.com', $data['email']);
        self::assertSame('Doe', $data['lastName']);
    }

    public function testUpdateClientRejectsUnwritableFields(): void
    {
        $client = $this->createClientEntity();
        $this->browser->jsonRequest('PATCH', '/clients/' . $client->getId(), [
            'id' => 123,
            'unknownField' => 'value',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $data = json_decode($this->browser->getResponse()->getContent(), true);
        self::assertContains('id', $data['errors']['fields']);
        self::assertContains('unknownField', $data['errors']['fields']);
    }

    public function testDeleteClient(): void
    {
        $client = $this->createClientEntity();
        $clientId = $client->getId();
        $this->browser->jsonRequest('DELETE', '/clients/' . $clientId);
        self::assertResponseIsSuccessful();
        self::assertNull($this->entityManager->getRepository(Client::class)->find($clientId));
    }

    public function testDeleteMissingClient(): void
    {
        $this->browser->jsonRequest('DELETE', '/clients/999999');
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
