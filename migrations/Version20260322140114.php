<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260322140114 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Fred note: SQLite impose une valeur par defaut quand j'ajoute une colonne NOT NULL sur une table deja remplie.
        $this->addSql("ALTER TABLE site_settings ADD COLUMN preset_name VARCHAR(120) NOT NULL DEFAULT 'Classique'");
        $this->addSql('ALTER TABLE site_settings ADD COLUMN preset_key VARCHAR(80) DEFAULT NULL');
        $this->addSql('ALTER TABLE site_settings ADD COLUMN is_active BOOLEAN NOT NULL DEFAULT 1');
        $this->addSql("UPDATE site_settings SET preset_key = 'default' WHERE preset_key IS NULL");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__site_settings AS SELECT id, site_name, tagline, header_logo, home_hero_background, home_hero_visual, home_overview_image_one, home_overview_image_two, home_overview_image_three, shop_hero_background, home_hero_title, home_hero_text, home_special_offer_eyebrow, home_special_offer_title, home_special_offer_text, home_special_offer_button_label, home_special_offer_button_url, updated_at FROM site_settings');
        $this->addSql('DROP TABLE site_settings');
        $this->addSql('CREATE TABLE site_settings (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, site_name VARCHAR(180) NOT NULL, tagline CLOB DEFAULT NULL, header_logo VARCHAR(255) DEFAULT NULL, home_hero_background VARCHAR(255) DEFAULT NULL, home_hero_visual VARCHAR(255) DEFAULT NULL, home_overview_image_one VARCHAR(255) DEFAULT NULL, home_overview_image_two VARCHAR(255) DEFAULT NULL, home_overview_image_three VARCHAR(255) DEFAULT NULL, shop_hero_background VARCHAR(255) DEFAULT NULL, home_hero_title VARCHAR(255) DEFAULT NULL, home_hero_text CLOB DEFAULT NULL, home_special_offer_eyebrow VARCHAR(120) DEFAULT NULL, home_special_offer_title VARCHAR(255) DEFAULT NULL, home_special_offer_text CLOB DEFAULT NULL, home_special_offer_button_label VARCHAR(120) DEFAULT NULL, home_special_offer_button_url VARCHAR(255) DEFAULT NULL, updated_at DATETIME NOT NULL)');
        $this->addSql('INSERT INTO site_settings (id, site_name, tagline, header_logo, home_hero_background, home_hero_visual, home_overview_image_one, home_overview_image_two, home_overview_image_three, shop_hero_background, home_hero_title, home_hero_text, home_special_offer_eyebrow, home_special_offer_title, home_special_offer_text, home_special_offer_button_label, home_special_offer_button_url, updated_at) SELECT id, site_name, tagline, header_logo, home_hero_background, home_hero_visual, home_overview_image_one, home_overview_image_two, home_overview_image_three, shop_hero_background, home_hero_title, home_hero_text, home_special_offer_eyebrow, home_special_offer_title, home_special_offer_text, home_special_offer_button_label, home_special_offer_button_url, updated_at FROM __temp__site_settings');
        $this->addSql('DROP TABLE __temp__site_settings');
    }
}
