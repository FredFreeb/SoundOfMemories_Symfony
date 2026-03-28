<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260322143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute l image et les prix avant/apres de la baniere offre speciale';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE site_settings ADD home_special_offer_image VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE site_settings ADD home_special_offer_price_before VARCHAR(80) DEFAULT NULL');
        $this->addSql('ALTER TABLE site_settings ADD home_special_offer_price_after VARCHAR(80) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE site_settings DROP home_special_offer_image');
        $this->addSql('ALTER TABLE site_settings DROP home_special_offer_price_before');
        $this->addSql('ALTER TABLE site_settings DROP home_special_offer_price_after');
    }
}
