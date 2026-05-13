<?php

namespace App\Entity;

use App\Entity\LoanEntity;
use App\Repository\ClientRepository;
use App\Validator\Constraints\LatinName;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
class Client extends LoanEntity
{
    public CONST READABLE = ['id', 'firstName', 'lastName', 'email', 'phoneNumber'];
    public CONST WRITABLE = ['firstName', 'lastName', 'email', 'phoneNumber'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 32)]
    #[LatinName]
    private ?string $firstName = null;

    #[ORM\Column(length: 32)]
    #[LatinName]
    private ?string $lastName = null;

    #[ORM\Column(length: 255)]
    #[Assert\Sequentially([
        new Assert\NotBlank,
        new Assert\Email
    ])]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    #[Assert\Sequentially([
        new Assert\NotBlank,
        new Assert\Regex(pattern: '/^\+[1-9]\d{1,14}$/', message: 'Invalid phone number')
    ])]
    private ?string $phoneNumber = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(string $phoneNumber): static
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function toArray(): array
    {
        $arr = [];
        foreach(self::READABLE as $field) {
            $getter = 'get' . ucfirst($field);
            $arr[$field] = $this->$getter();
        }
        return $arr;
    }
}
