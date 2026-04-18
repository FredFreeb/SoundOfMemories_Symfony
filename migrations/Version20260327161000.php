<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260327161000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute une affiche optionnelle aux concerts';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('concert');
        if (!$table->hasColumn('poster_image')) {
            $table->addColumn('poster_image', 'string', ['length' => 255, 'notnull' => false]);
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('concert');
        if ($table->hasColumn('poster_image')) {
            $table->dropColumn('poster_image');
        }
    }
}
