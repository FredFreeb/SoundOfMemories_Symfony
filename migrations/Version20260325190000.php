<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260325190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les champs de livraison Sendcloud-ready sur les commandes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `order` ADD shipping_country_code VARCHAR(2) DEFAULT NULL');
        $this->addSql('ALTER TABLE `order` ADD shipping_provider VARCHAR(120) DEFAULT NULL');
        $this->addSql('ALTER TABLE `order` ADD shipping_method_code VARCHAR(190) DEFAULT NULL');
        $this->addSql('ALTER TABLE `order` ADD shipping_method_label VARCHAR(190) DEFAULT NULL');
        $this->addSql('ALTER TABLE `order` ADD shipping_rate_cents INTEGER DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `order` DROP shipping_country_code');
        $this->addSql('ALTER TABLE `order` DROP shipping_provider');
        $this->addSql('ALTER TABLE `order` DROP shipping_method_code');
        $this->addSql('ALTER TABLE `order` DROP shipping_method_label');
        $this->addSql('ALTER TABLE `order` DROP shipping_rate_cents');
    }
}
