<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20260326153000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la table gallery_photo pour piloter la galerie photo depuis l admin.';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('gallery_photo')) {
            return;
        }

        $table = $schema->createTable('gallery_photo');
        $table->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $table->addColumn('image_path', Types::STRING, ['length' => 255]);
        $table->addColumn('title', Types::STRING, ['length' => 180, 'notnull' => false]);
        $table->addColumn('alt_text', Types::STRING, ['length' => 180, 'notnull' => false]);
        $table->addColumn('caption', Types::TEXT, ['notnull' => false]);
        $table->addColumn('position', Types::INTEGER);
        $table->addColumn('is_published', Types::BOOLEAN, ['default' => true]);
        $table->addColumn('created_at', Types::DATETIME_IMMUTABLE);
        $table->addColumn('updated_at', Types::DATETIME_IMMUTABLE);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['is_published', 'position'], 'idx_gallery_photo_publish_position');
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('gallery_photo')) {
            $schema->dropTable('gallery_photo');
        }
    }
}
