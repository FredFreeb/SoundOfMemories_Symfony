<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260322074500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute un extrait court dedie a la page catalogue des produits.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product ADD COLUMN catalog_excerpt CLOB DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TEMPORARY TABLE __temp__product AS SELECT id, name, slug, short_description, description, price_cents, stock, cover_image, is_published, created_at, updated_at, category_id, animation_key FROM product');
        $this->addSql('DROP TABLE product');
        $this->addSql('CREATE TABLE product (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(180) NOT NULL, slug VARCHAR(180) NOT NULL, short_description CLOB DEFAULT NULL, description CLOB DEFAULT NULL, price_cents INTEGER NOT NULL, stock INTEGER NOT NULL, cover_image VARCHAR(255) DEFAULT NULL, is_published BOOLEAN NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, category_id INTEGER DEFAULT NULL, animation_key VARCHAR(80) DEFAULT NULL, CONSTRAINT FK_D34A04AD12469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO product (id, name, slug, short_description, description, price_cents, stock, cover_image, is_published, created_at, updated_at, category_id, animation_key) SELECT id, name, slug, short_description, description, price_cents, stock, cover_image, is_published, created_at, updated_at, category_id, animation_key FROM __temp__product');
        $this->addSql('DROP TABLE __temp__product');
        $this->addSql('CREATE INDEX IDX_D34A04AD12469DE2 ON product (category_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D34A04AD989D9B62 ON product (slug)');
    }
}
