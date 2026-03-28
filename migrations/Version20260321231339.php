<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260321231339 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE site_settings (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, site_name VARCHAR(180) NOT NULL, tagline CLOB DEFAULT NULL, header_logo VARCHAR(255) DEFAULT NULL, home_hero_background VARCHAR(255) DEFAULT NULL, home_hero_visual VARCHAR(255) DEFAULT NULL, shop_hero_background VARCHAR(255) DEFAULT NULL, home_hero_title VARCHAR(255) DEFAULT NULL, home_hero_text CLOB DEFAULT NULL, updated_at DATETIME NOT NULL)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__order AS SELECT id, status, customer_email, customer_name, customer_phone, shipping_address, postal_code, city, note, total_cents, payment_status, payment_provider, payment_reference, stripe_checkout_session_id, created_at, paid_at FROM "order"');
        $this->addSql('DROP TABLE "order"');
        $this->addSql('CREATE TABLE "order" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, status VARCHAR(40) NOT NULL, customer_email VARCHAR(180) NOT NULL, customer_name VARCHAR(180) NOT NULL, customer_phone VARCHAR(40) DEFAULT NULL, shipping_address VARCHAR(255) DEFAULT NULL, postal_code VARCHAR(20) DEFAULT NULL, city VARCHAR(120) DEFAULT NULL, note CLOB DEFAULT NULL, total_cents INTEGER NOT NULL, payment_status VARCHAR(40) DEFAULT NULL, payment_provider VARCHAR(120) DEFAULT NULL, payment_reference VARCHAR(190) DEFAULT NULL, stripe_checkout_session_id VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, paid_at DATETIME DEFAULT NULL, customer_account_id INTEGER DEFAULT NULL, CONSTRAINT FK_F529939866A25B38 FOREIGN KEY (customer_account_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO "order" (id, status, customer_email, customer_name, customer_phone, shipping_address, postal_code, city, note, total_cents, payment_status, payment_provider, payment_reference, stripe_checkout_session_id, created_at, paid_at) SELECT id, status, customer_email, customer_name, customer_phone, shipping_address, postal_code, city, note, total_cents, payment_status, payment_provider, payment_reference, stripe_checkout_session_id, created_at, paid_at FROM __temp__order');
        $this->addSql('DROP TABLE __temp__order');
        $this->addSql('CREATE INDEX IDX_F529939866A25B38 ON "order" (customer_account_id)');
        $this->addSql('ALTER TABLE user ADD COLUMN phone VARCHAR(40) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD COLUMN default_address VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD COLUMN postal_code VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD COLUMN city VARCHAR(120) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE site_settings');
        $this->addSql('CREATE TEMPORARY TABLE __temp__order AS SELECT id, status, customer_email, customer_name, customer_phone, shipping_address, postal_code, city, note, total_cents, payment_status, payment_provider, payment_reference, stripe_checkout_session_id, created_at, paid_at FROM "order"');
        $this->addSql('DROP TABLE "order"');
        $this->addSql('CREATE TABLE "order" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, status VARCHAR(40) NOT NULL, customer_email VARCHAR(180) NOT NULL, customer_name VARCHAR(180) NOT NULL, customer_phone VARCHAR(40) DEFAULT NULL, shipping_address VARCHAR(255) DEFAULT NULL, postal_code VARCHAR(20) DEFAULT NULL, city VARCHAR(120) DEFAULT NULL, note CLOB DEFAULT NULL, total_cents INTEGER NOT NULL, payment_status VARCHAR(40) DEFAULT NULL, payment_provider VARCHAR(120) DEFAULT NULL, payment_reference VARCHAR(190) DEFAULT NULL, stripe_checkout_session_id VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, paid_at DATETIME DEFAULT NULL)');
        $this->addSql('INSERT INTO "order" (id, status, customer_email, customer_name, customer_phone, shipping_address, postal_code, city, note, total_cents, payment_status, payment_provider, payment_reference, stripe_checkout_session_id, created_at, paid_at) SELECT id, status, customer_email, customer_name, customer_phone, shipping_address, postal_code, city, note, total_cents, payment_status, payment_provider, payment_reference, stripe_checkout_session_id, created_at, paid_at FROM __temp__order');
        $this->addSql('DROP TABLE __temp__order');
        $this->addSql('CREATE TEMPORARY TABLE __temp__user AS SELECT id, email, roles, password, full_name FROM "user"');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('CREATE TABLE "user" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL, full_name VARCHAR(120) NOT NULL)');
        $this->addSql('INSERT INTO "user" (id, email, roles, password, full_name) SELECT id, email, roles, password, full_name FROM __temp__user');
        $this->addSql('DROP TABLE __temp__user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');
    }
}
