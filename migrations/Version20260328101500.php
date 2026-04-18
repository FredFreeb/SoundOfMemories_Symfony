<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260328101500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute un token d acces public pour la confirmation de commande';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();

        if ($platform instanceof SQLitePlatform) {
            $this->addSql("ALTER TABLE `order` ADD COLUMN checkout_access_token VARCHAR(64) DEFAULT NULL");
            $this->addSql("UPDATE `order` SET checkout_access_token = lower(hex(randomblob(16))) WHERE checkout_access_token IS NULL OR checkout_access_token = ''");
        } elseif ($platform instanceof AbstractMySQLPlatform) {
            $this->addSql("ALTER TABLE `order` ADD checkout_access_token VARCHAR(64) DEFAULT NULL");
            $this->addSql("UPDATE `order` SET checkout_access_token = lower(hex(random_bytes(16))) WHERE checkout_access_token IS NULL OR checkout_access_token = ''");
        } else {
            $table = $schema->getTable('order');
            if (!$table->hasColumn('checkout_access_token')) {
                $table->addColumn('checkout_access_token', 'string', ['length' => 64, 'notnull' => false]);
            }
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('order');
        if ($table->hasColumn('checkout_access_token')) {
            $table->dropColumn('checkout_access_token');
        }
    }
}
