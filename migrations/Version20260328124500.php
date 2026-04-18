<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260328124500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le pays de livraison au compte fan';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();

        if ($platform instanceof SQLitePlatform) {
            $this->addSql("ALTER TABLE `user` ADD COLUMN country_code VARCHAR(2) DEFAULT NULL");
        } elseif ($platform instanceof AbstractMySQLPlatform) {
            $this->addSql("ALTER TABLE `user` ADD country_code VARCHAR(2) DEFAULT NULL");
        } else {
            $table = $schema->getTable('user');
            if (!$table->hasColumn('country_code')) {
                $table->addColumn('country_code', 'string', ['length' => 2, 'notnull' => false]);
            }
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('user');
        if ($table->hasColumn('country_code')) {
            $table->dropColumn('country_code');
        }
    }
}
