<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
// Fred note: Une ligne de commande garde une photo simple du produit vendu au moment de l'achat.
class OrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private string $productName = '';

    #[ORM\Column(nullable: true)]
    private ?int $productIdSnapshot = null;

    #[ORM\Column]
    private int $quantity = 1;

    #[ORM\Column]
    private int $unitPriceCents = 0;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Order $orderRef = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    // Fred note: Ce texte sert surtout a rendre les lignes lisibles dans le detail d'une commande.
    public function __toString(): string
    {
        return sprintf('%s x%d', $this->productName, $this->quantity);
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function setProductName(string $productName): static
    {
        $this->productName = $productName;

        return $this;
    }

    public function getProductIdSnapshot(): ?int
    {
        return $this->productIdSnapshot;
    }

    public function setProductIdSnapshot(?int $productIdSnapshot): static
    {
        $this->productIdSnapshot = $productIdSnapshot;

        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getUnitPriceCents(): int
    {
        return $this->unitPriceCents;
    }

    public function getFormattedUnitPrice(): string
    {
        // Fred note: Le prix unitaire reste stocke en centimes pour eviter les erreurs de calcul.
        return number_format($this->unitPriceCents / 100, 2, ',', ' ') . ' EUR';
    }

    public function setUnitPriceCents(int $unitPriceCents): static
    {
        $this->unitPriceCents = $unitPriceCents;

        return $this;
    }

    public function getLineTotalCents(): int
    {
        return $this->unitPriceCents * $this->quantity;
    }

    public function getOrderRef(): ?Order
    {
        return $this->orderRef;
    }

    public function setOrderRef(?Order $order): static
    {
        $this->orderRef = $order;

        return $this;
    }
}
