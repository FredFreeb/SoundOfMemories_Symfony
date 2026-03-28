<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260325160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le consentement marketing, la clôture de compte et le suivi de réduction de bienvenue sur les comptes et commandes.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TEMPORARY TABLE __temp__user AS SELECT id, email, roles, password, full_name, avatar_path, google_id, apple_id, is_verified, verified_at, phone, default_address, address_building, address_extra, postal_code, city FROM "user"');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('CREATE TABLE "user" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL, full_name VARCHAR(120) NOT NULL, avatar_path VARCHAR(255) DEFAULT NULL, google_id VARCHAR(191) DEFAULT NULL, apple_id VARCHAR(191) DEFAULT NULL, is_verified BOOLEAN DEFAULT 0 NOT NULL, verified_at DATETIME DEFAULT NULL, phone VARCHAR(40) DEFAULT NULL, default_address VARCHAR(255) DEFAULT NULL, address_building VARCHAR(120) DEFAULT NULL, address_extra VARCHAR(160) DEFAULT NULL, postal_code VARCHAR(20) DEFAULT NULL, city VARCHAR(120) DEFAULT NULL, marketing_opt_in BOOLEAN DEFAULT 0 NOT NULL, marketing_consent_at DATETIME DEFAULT NULL, marketing_revoked_at DATETIME DEFAULT NULL, welcome_discount_used_at DATETIME DEFAULT NULL, account_closed_at DATETIME DEFAULT NULL)');
        $this->addSql('INSERT INTO "user" (id, email, roles, password, full_name, avatar_path, google_id, apple_id, is_verified, verified_at, phone, default_address, address_building, address_extra, postal_code, city, marketing_opt_in, marketing_consent_at, marketing_revoked_at, welcome_discount_used_at, account_closed_at) SELECT id, email, roles, password, full_name, avatar_path, google_id, apple_id, is_verified, verified_at, phone, default_address, address_building, address_extra, postal_code, city, 0, NULL, NULL, NULL, NULL FROM __temp__user');
        $this->addSql('DROP TABLE __temp__user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D64976F5C865 ON "user" (google_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649571323F9 ON "user" (apple_id)');

        $this->addSql('CREATE TEMPORARY TABLE __temp__order AS SELECT id, status, customer_email, customer_name, customer_phone, shipping_address, postal_code, city, note, total_cents, payment_status, delivery_status, payment_provider, payment_reference, shipping_carrier, tracking_number, tracking_url, mollie_payment_id, stripe_checkout_session_id, created_at, paid_at, customer_account_id FROM "order"');
        $this->addSql('DROP TABLE "order"');
        $this->addSql('CREATE TABLE "order" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, status VARCHAR(40) NOT NULL, customer_email VARCHAR(180) NOT NULL, customer_name VARCHAR(180) NOT NULL, customer_phone VARCHAR(40) DEFAULT NULL, shipping_address VARCHAR(255) DEFAULT NULL, postal_code VARCHAR(20) DEFAULT NULL, city VARCHAR(120) DEFAULT NULL, note CLOB DEFAULT NULL, total_cents INTEGER NOT NULL, subtotal_cents INTEGER NOT NULL, discount_cents INTEGER NOT NULL, discount_label VARCHAR(180) DEFAULT NULL, payment_status VARCHAR(40) DEFAULT NULL, delivery_status VARCHAR(40) DEFAULT \'pending\' NOT NULL, payment_provider VARCHAR(120) DEFAULT NULL, payment_reference VARCHAR(190) DEFAULT NULL, shipping_carrier VARCHAR(120) DEFAULT NULL, tracking_number VARCHAR(120) DEFAULT NULL, tracking_url VARCHAR(255) DEFAULT NULL, mollie_payment_id VARCHAR(255) DEFAULT NULL, stripe_checkout_session_id VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, paid_at DATETIME DEFAULT NULL, customer_account_id INTEGER DEFAULT NULL, CONSTRAINT FK_F529939866A25B38 FOREIGN KEY (customer_account_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO "order" (id, status, customer_email, customer_name, customer_phone, shipping_address, postal_code, city, note, total_cents, subtotal_cents, discount_cents, discount_label, payment_status, delivery_status, payment_provider, payment_reference, shipping_carrier, tracking_number, tracking_url, mollie_payment_id, stripe_checkout_session_id, created_at, paid_at, customer_account_id) SELECT id, status, customer_email, customer_name, customer_phone, shipping_address, postal_code, city, note, total_cents, total_cents, 0, NULL, payment_status, delivery_status, payment_provider, payment_reference, shipping_carrier, tracking_number, tracking_url, mollie_payment_id, stripe_checkout_session_id, created_at, paid_at, customer_account_id FROM __temp__order');
        $this->addSql('DROP TABLE __temp__order');
        $this->addSql('CREATE INDEX IDX_F529939866A25B38 ON "order" (customer_account_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TEMPORARY TABLE __temp__order AS SELECT id, status, customer_email, customer_name, customer_phone, shipping_address, postal_code, city, note, total_cents, payment_status, delivery_status, payment_provider, payment_reference, shipping_carrier, tracking_number, tracking_url, mollie_payment_id, stripe_checkout_session_id, created_at, paid_at, customer_account_id FROM "order"');
        $this->addSql('DROP TABLE "order"');
        $this->addSql('CREATE TABLE "order" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, status VARCHAR(40) NOT NULL, customer_email VARCHAR(180) NOT NULL, customer_name VARCHAR(180) NOT NULL, customer_phone VARCHAR(40) DEFAULT NULL, shipping_address VARCHAR(255) DEFAULT NULL, postal_code VARCHAR(20) DEFAULT NULL, city VARCHAR(120) DEFAULT NULL, note CLOB DEFAULT NULL, total_cents INTEGER NOT NULL, payment_status VARCHAR(40) DEFAULT NULL, delivery_status VARCHAR(40) DEFAULT \'pending\' NOT NULL, payment_provider VARCHAR(120) DEFAULT NULL, payment_reference VARCHAR(190) DEFAULT NULL, shipping_carrier VARCHAR(120) DEFAULT NULL, tracking_number VARCHAR(120) DEFAULT NULL, tracking_url VARCHAR(255) DEFAULT NULL, mollie_payment_id VARCHAR(255) DEFAULT NULL, stripe_checkout_session_id VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, paid_at DATETIME DEFAULT NULL, customer_account_id INTEGER DEFAULT NULL, CONSTRAINT FK_F529939866A25B38 FOREIGN KEY (customer_account_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO "order" (id, status, customer_email, customer_name, customer_phone, shipping_address, postal_code, city, note, total_cents, payment_status, delivery_status, payment_provider, payment_reference, shipping_carrier, tracking_number, tracking_url, mollie_payment_id, stripe_checkout_session_id, created_at, paid_at, customer_account_id) SELECT id, status, customer_email, customer_name, customer_phone, shipping_address, postal_code, city, note, total_cents, payment_status, delivery_status, payment_provider, payment_reference, shipping_carrier, tracking_number, tracking_url, mollie_payment_id, stripe_checkout_session_id, created_at, paid_at, customer_account_id FROM __temp__order');
        $this->addSql('DROP TABLE __temp__order');
        $this->addSql('CREATE INDEX IDX_F529939866A25B38 ON "order" (customer_account_id)');

        $this->addSql('CREATE TEMPORARY TABLE __temp__user AS SELECT id, email, roles, password, full_name, avatar_path, google_id, apple_id, is_verified, verified_at, phone, default_address, address_building, address_extra, postal_code, city FROM "user"');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('CREATE TABLE "user" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL, full_name VARCHAR(120) NOT NULL, avatar_path VARCHAR(255) DEFAULT NULL, google_id VARCHAR(191) DEFAULT NULL, apple_id VARCHAR(191) DEFAULT NULL, is_verified BOOLEAN DEFAULT 0 NOT NULL, verified_at DATETIME DEFAULT NULL, phone VARCHAR(40) DEFAULT NULL, default_address VARCHAR(255) DEFAULT NULL, address_building VARCHAR(120) DEFAULT NULL, address_extra VARCHAR(160) DEFAULT NULL, postal_code VARCHAR(20) DEFAULT NULL, city VARCHAR(120) DEFAULT NULL)');
        $this->addSql('INSERT INTO "user" (id, email, roles, password, full_name, avatar_path, google_id, apple_id, is_verified, verified_at, phone, default_address, address_building, address_extra, postal_code, city) SELECT id, email, roles, password, full_name, avatar_path, google_id, apple_id, is_verified, verified_at, phone, default_address, address_building, address_extra, postal_code, city FROM __temp__user');
        $this->addSql('DROP TABLE __temp__user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D64976F5C865 ON "user" (google_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649571323F9 ON "user" (apple_id)');
    }
}
