<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260322124500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les champs de baniere offre speciale pour la home';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE site_settings ADD home_special_offer_eyebrow VARCHAR(120) DEFAULT NULL');
        $this->addSql('ALTER TABLE site_settings ADD home_special_offer_title VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE site_settings ADD home_special_offer_text CLOB DEFAULT NULL');
        $this->addSql('ALTER TABLE site_settings ADD home_special_offer_button_label VARCHAR(120) DEFAULT NULL');
        $this->addSql('ALTER TABLE site_settings ADD home_special_offer_button_url VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE site_settings DROP COLUMN home_special_offer_eyebrow');
        $this->addSql('ALTER TABLE site_settings DROP COLUMN home_special_offer_title');
        $this->addSql('ALTER TABLE site_settings DROP COLUMN home_special_offer_text');
        $this->addSql('ALTER TABLE site_settings DROP COLUMN home_special_offer_button_label');
        $this->addSql('ALTER TABLE site_settings DROP COLUMN home_special_offer_button_url');
    }
}
