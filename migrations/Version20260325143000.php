<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260325143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le bâtiment et le complément d’adresse sur les comptes utilisateurs.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD COLUMN address_building VARCHAR(120) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD COLUMN address_extra VARCHAR(160) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TEMPORARY TABLE __temp__user AS SELECT id, email, roles, password, full_name, avatar_path, is_verified, verified_at, phone, default_address, postal_code, city, google_id, apple_id FROM "user"');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('CREATE TABLE "user" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL, full_name VARCHAR(120) NOT NULL, avatar_path VARCHAR(255) DEFAULT NULL, google_id VARCHAR(191) DEFAULT NULL, apple_id VARCHAR(191) DEFAULT NULL, is_verified BOOLEAN DEFAULT 1 NOT NULL, verified_at DATETIME DEFAULT NULL, phone VARCHAR(40) DEFAULT NULL, default_address VARCHAR(255) DEFAULT NULL, postal_code VARCHAR(20) DEFAULT NULL, city VARCHAR(120) DEFAULT NULL)');
        $this->addSql('INSERT INTO "user" (id, email, roles, password, full_name, avatar_path, google_id, apple_id, is_verified, verified_at, phone, default_address, postal_code, city) SELECT id, email, roles, password, full_name, avatar_path, google_id, apple_id, is_verified, verified_at, phone, default_address, postal_code, city FROM __temp__user');
        $this->addSql('DROP TABLE __temp__user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D64976F5C865 ON "user" (google_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649571323F9 ON "user" (apple_id)');
    }
}
