<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260325121500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les identifiants Google et Apple sur les comptes, ainsi que la table de reinitialisation du mot de passe.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE reset_password_request (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, selector VARCHAR(20) NOT NULL, hashed_token VARCHAR(100) NOT NULL, requested_at DATETIME NOT NULL, expires_at DATETIME NOT NULL, user_id INTEGER NOT NULL, CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_7CE748AA76ED395 ON reset_password_request (user_id)');
        $this->addSql('ALTER TABLE "user" ADD COLUMN google_id VARCHAR(191) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD COLUMN apple_id VARCHAR(191) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D64976F5C865 ON "user" (google_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649571323F9 ON "user" (apple_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE reset_password_request');
        $this->addSql('DROP INDEX UNIQ_8D93D64976F5C865');
        $this->addSql('DROP INDEX UNIQ_8D93D649571323F9');
        $this->addSql('CREATE TEMPORARY TABLE __temp__user AS SELECT id, email, roles, password, full_name, is_verified, verified_at, phone, default_address, postal_code, city FROM "user"');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('CREATE TABLE "user" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL, full_name VARCHAR(120) NOT NULL, is_verified BOOLEAN DEFAULT 1 NOT NULL, verified_at DATETIME DEFAULT NULL, phone VARCHAR(40) DEFAULT NULL, default_address VARCHAR(255) DEFAULT NULL, postal_code VARCHAR(20) DEFAULT NULL, city VARCHAR(120) DEFAULT NULL)');
        $this->addSql('INSERT INTO "user" (id, email, roles, password, full_name, is_verified, verified_at, phone, default_address, postal_code, city) SELECT id, email, roles, password, full_name, is_verified, verified_at, phone, default_address, postal_code, city FROM __temp__user');
        $this->addSql('DROP TABLE __temp__user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');
    }
}
