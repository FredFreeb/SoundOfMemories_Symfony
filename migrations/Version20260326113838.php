<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20260326113838 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la table concert pour la gestion des dates live Sound Of Memories.';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('concert')) {
            return;
        }

        $table = $schema->createTable('concert');
        $table->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $table->addColumn('title', Types::STRING, ['length' => 180]);
        $table->addColumn('venue', Types::STRING, ['length' => 180]);
        $table->addColumn('city', Types::STRING, ['length' => 120]);
        $table->addColumn('country', Types::STRING, ['length' => 120]);
        $table->addColumn('concert_at', Types::DATETIME_IMMUTABLE);
        $table->addColumn('details', Types::TEXT, ['notnull' => false]);
        $table->addColumn('ticket_url', Types::STRING, ['length' => 255, 'notnull' => false]);
        $table->addColumn('status', Types::STRING, ['length' => 30]);
        $table->addColumn('is_highlighted', Types::BOOLEAN, ['default' => false]);
        $table->addColumn('is_published', Types::BOOLEAN, ['default' => true]);
        $table->addColumn('created_at', Types::DATETIME_IMMUTABLE);
        $table->addColumn('updated_at', Types::DATETIME_IMMUTABLE);
        $table->setPrimaryKey(['id']);
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('concert')) {
            $schema->dropTable('concert');
        }
    }
}
