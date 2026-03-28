<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260322080500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la gestion admin des avis et retours presse avec photo.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE press_mention (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, author_name VARCHAR(180) NOT NULL, source_label VARCHAR(180) DEFAULT NULL, quote_primary CLOB NOT NULL, quote_secondary CLOB DEFAULT NULL, link_url VARCHAR(255) DEFAULT NULL, link_label VARCHAR(255) DEFAULT NULL, photo VARCHAR(255) DEFAULT NULL, position INTEGER NOT NULL, is_published BOOLEAN NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE press_mention');
    }
}
