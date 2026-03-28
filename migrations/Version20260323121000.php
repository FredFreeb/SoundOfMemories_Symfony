<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260323121000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les reperes produit et l offre du mois geree au niveau du catalogue';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product ADD offer_banner_image VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD is_monthly_offer BOOLEAN NOT NULL DEFAULT FALSE');
        $this->addSql('ALTER TABLE product ADD offer_banner_eyebrow VARCHAR(120) DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD offer_banner_title VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD offer_banner_text CLOB DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD offer_banner_price_before VARCHAR(80) DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD offer_banner_price_after VARCHAR(80) DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD reading_level INTEGER DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD difficulty_level INTEGER DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD maturity_level INTEGER DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD ambiance_level INTEGER DEFAULT NULL');

        $this->addSql('UPDATE product SET reading_level = 3 WHERE reading_level IS NULL');
        $this->addSql('UPDATE product SET difficulty_level = 3 WHERE difficulty_level IS NULL');
        $this->addSql('UPDATE product SET maturity_level = 3 WHERE maturity_level IS NULL');
        $this->addSql('UPDATE product SET ambiance_level = 4 WHERE ambiance_level IS NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product DROP offer_banner_image');
        $this->addSql('ALTER TABLE product DROP is_monthly_offer');
        $this->addSql('ALTER TABLE product DROP offer_banner_eyebrow');
        $this->addSql('ALTER TABLE product DROP offer_banner_title');
        $this->addSql('ALTER TABLE product DROP offer_banner_text');
        $this->addSql('ALTER TABLE product DROP offer_banner_price_before');
        $this->addSql('ALTER TABLE product DROP offer_banner_price_after');
        $this->addSql('ALTER TABLE product DROP reading_level');
        $this->addSql('ALTER TABLE product DROP difficulty_level');
        $this->addSql('ALTER TABLE product DROP maturity_level');
        $this->addSql('ALTER TABLE product DROP ambiance_level');
    }
}
