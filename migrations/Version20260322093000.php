<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260322093000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute une galerie d images secondaire par produit.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE product_gallery_image (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, product_id INTEGER NOT NULL, image_path VARCHAR(255) NOT NULL, alt_text VARCHAR(180) DEFAULT NULL, position INTEGER NOT NULL, CONSTRAINT FK_12FD0FB94584665A FOREIGN KEY (product_id) REFERENCES product (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_12FD0FB94584665A ON product_gallery_image (product_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE product_gallery_image');
    }
}
