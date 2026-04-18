<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260404103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute une fenêtre temporelle de promotion pour les produits merch.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product ADD COLUMN promotion_starts_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD COLUMN promotion_ends_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigration('Cette migration ajoute une fenêtre de promotion sans rollback automatique prévu.');
    }
}
