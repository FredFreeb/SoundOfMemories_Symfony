<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260328165310 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les variantes produit et les snapshots de variante dans les lignes de commande.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE product_variant (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                label VARCHAR(120) NOT NULL,
                sku VARCHAR(120) DEFAULT NULL,
                price_cents INTEGER NOT NULL,
                compare_at_price_cents INTEGER DEFAULT NULL,
                stock INTEGER NOT NULL,
                position INTEGER NOT NULL,
                is_default BOOLEAN NOT NULL,
                is_published BOOLEAN NOT NULL,
                product_id INTEGER NOT NULL,
                CONSTRAINT FK_209AA41D4584665A FOREIGN KEY (product_id) REFERENCES product (id) NOT DEFERRABLE INITIALLY IMMEDIATE
            )
        SQL);
        $this->addSql('CREATE INDEX IDX_209AA41D4584665A ON product_variant (product_id)');
        $this->addSql('ALTER TABLE product ADD COLUMN variant_choice_label VARCHAR(120) DEFAULT NULL');
        $this->addSql('ALTER TABLE order_item ADD COLUMN product_variant_id_snapshot INTEGER DEFAULT NULL');
        $this->addSql('ALTER TABLE order_item ADD COLUMN variant_label VARCHAR(120) DEFAULT NULL');
        $this->addSql('ALTER TABLE order_item ADD COLUMN variant_sku VARCHAR(120) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigration('Cette migration SQLite ajoute des colonnes sans rollback propre prévu.');
    }
}
