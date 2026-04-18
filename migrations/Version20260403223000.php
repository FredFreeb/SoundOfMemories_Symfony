<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260403223000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute des champs merchandising avancés pour les produits.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product ADD COLUMN merch_badge VARCHAR(120) DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD COLUMN merch_badge_tone VARCHAR(40) DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD COLUMN sort_position INTEGER NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE product ADD COLUMN feature_one VARCHAR(180) DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD COLUMN feature_two VARCHAR(180) DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD COLUMN feature_three VARCHAR(180) DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD COLUMN fit_details VARCHAR(180) DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD COLUMN material_details VARCHAR(180) DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD COLUMN shipping_details VARCHAR(180) DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD COLUMN size_guide_text VARCHAR(180) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigration('Cette migration ajoute des champs merchandising sans rollback automatique prévu.');
    }
}
