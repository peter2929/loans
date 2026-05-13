<?php

namespace App\Entity;

use App\Entity\LoanEntity;
use App\Repository\ApplicationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ApplicationRepository::class)]
class Application extends LoanEntity
{
    public CONST READABLE = ['id', 'client', 'term', 'amount', 'currency'];
    public CONST WRITABLE = ['client', 'term', 'amount', 'currency'];
    public CONST RELATIONS = ['client'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client = null;

    #[ORM\Column]
    #[Assert\Sequentially([
        new Assert\NotBlank,
        new Assert\Range(min: 10, max: 30)
    ])]
    private ?int $term = null;

    #[ORM\Column]
    #[Assert\Sequentially([
        new Assert\NotBlank,
        new Assert\Range(min: 100.00, max: 5000.00)
    ])]
    private ?float $amount = null;

    #[ORM\Column(length: 3)]
    #[Assert\Sequentially([
        new Assert\NotBlank,
        new Assert\EqualTo('EUR')
    ])]
    private ?string $currency = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): static
    {
        $this->client = $client;

        return $this;
    }

    public function getTerm(): ?int
    {
        return $this->term;
    }

    public function setTerm(int $term): static
    {
        $this->term = $term;

        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function toArray(): array
    {
        $arr = [];
        foreach(self::READABLE as $field) {
            $getter = 'get' . ucfirst($field);
            if(isset( array_flip(self::RELATIONS)[$field] )) {
                $arr[$field . 'Id'] = $this->$getter()?->getId();
            }
            else {
                $arr[$field] = $this->$getter();
            }
        }
        return $arr;
    }
}
