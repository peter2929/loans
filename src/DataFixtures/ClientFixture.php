<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Client;

class ClientFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $client = new Client();
        $client->setFirstName('John');
        $client->setLastName('Doe');
        $client->setEmail('test@test.com');
        $client->setPhoneNumber('+37112345678');
        $manager->persist($client);

        $manager->flush();
    }
}
