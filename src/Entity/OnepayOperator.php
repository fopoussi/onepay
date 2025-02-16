<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\OnepayOperatorRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OnepayOperatorRepository::class)]
#[ApiResource]
class OnepayOperator
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $name = null;

    #[ORM\Column(nullable: true)]
    private ?float $commission_rate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $api_endpoint = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $api_key = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCommissionRate(): ?float
    {
        return $this->commission_rate;
    }

    public function setCommissionRate(?float $commission_rate): static
    {
        $this->commission_rate = $commission_rate;

        return $this;
    }

    public function getApiEndpoint(): ?string
    {
        return $this->api_endpoint;
    }

    public function setApiEndpoint(?string $api_endpoint): static
    {
        $this->api_endpoint = $api_endpoint;

        return $this;
    }

    public function getApiKey(): ?string
    {
        return $this->api_key;
    }

    public function setApiKey(?string $api_key): static
    {
        $this->api_key = $api_key;

        return $this;
    }
}
