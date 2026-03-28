<?php

namespace App\Entity;

use App\Repository\FaqEntryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FaqEntryRepository::class)]
// Fred note: Une entree FAQ represente une question/réponse pilotable depuis l'admin.
class FaqEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $question = null;

    #[ORM\Column(type: 'text')]
    private ?string $answer = null;

    #[ORM\Column]
    private bool $isPublished = true;

    #[ORM\Column]
    private int $position = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    // Fred note: Ce retour texte aide EasyAdmin a identifier rapidement l'entree courante.
    public function __toString(): string
    {
        return $this->question ?? 'Entree FAQ';
    }

    public function getQuestion(): ?string
    {
        return $this->question;
    }

    public function setQuestion(string $question): static
    {
        $this->question = $question;

        return $this;
    }

    public function getAnswer(): ?string
    {
        return $this->answer;
    }

    public function setAnswer(string $answer): static
    {
        $this->answer = $answer;

        return $this;
    }

    public function isPublished(): bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $isPublished): static
    {
        $this->isPublished = $isPublished;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }
}
