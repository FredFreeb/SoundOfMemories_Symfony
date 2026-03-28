<?php

namespace App\Entity;

use App\Repository\ConcertRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConcertRepository::class)]
class Concert
{
    public const STATUS_ANNOUNCED = 'announced';
    public const STATUS_SOLD_OUT = 'sold_out';
    public const STATUS_COMPLETED = 'completed';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $title = null;

    #[ORM\Column(length: 180)]
    private ?string $venue = null;

    #[ORM\Column(length: 120)]
    private ?string $city = null;

    #[ORM\Column(length: 120)]
    private ?string $country = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $concertAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $details = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ticketUrl = null;

    #[ORM\Column(length: 30)]
    private string $status = self::STATUS_ANNOUNCED;

    #[ORM\Column(options: ['default' => false])]
    private bool $isHighlighted = false;

    #[ORM\Column(options: ['default' => true])]
    private bool $isPublished = true;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public static function getStatusChoices(): array
    {
        return [
            'Annonce' => self::STATUS_ANNOUNCED,
            'Complet' => self::STATUS_SOLD_OUT,
            'Passé' => self::STATUS_COMPLETED,
        ];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function __toString(): string
    {
        return $this->title ?? 'Concert';
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        $this->touch();

        return $this;
    }

    public function getVenue(): ?string
    {
        return $this->venue;
    }

    public function setVenue(string $venue): static
    {
        $this->venue = $venue;
        $this->touch();

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;
        $this->touch();

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): static
    {
        $this->country = $country;
        $this->touch();

        return $this;
    }

    public function getConcertAt(): ?\DateTimeImmutable
    {
        return $this->concertAt;
    }

    public function setConcertAt(\DateTimeImmutable $concertAt): static
    {
        $this->concertAt = $concertAt;
        $this->touch();

        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(?string $details): static
    {
        $this->details = $details;
        $this->touch();

        return $this;
    }

    public function getTicketUrl(): ?string
    {
        return $this->ticketUrl;
    }

    public function setTicketUrl(?string $ticketUrl): static
    {
        $this->ticketUrl = $ticketUrl;
        $this->touch();

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        $this->touch();

        return $this;
    }

    public function isHighlighted(): bool
    {
        return $this->isHighlighted;
    }

    public function setIsHighlighted(bool $isHighlighted): static
    {
        $this->isHighlighted = $isHighlighted;
        $this->touch();

        return $this;
    }

    public function isPublished(): bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $isPublished): static
    {
        $this->isPublished = $isPublished;
        $this->touch();

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getFormattedDate(): string
    {
        return $this->concertAt?->format('d.m.Y H:i') ?? '';
    }

    public function getLocationLabel(): string
    {
        return trim(sprintf('%s, %s', $this->city, $this->country), ', ');
    }

    public function getStatusLabel(): string
    {
        return array_search($this->status, self::getStatusChoices(), true) ?: 'Annonce';
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
