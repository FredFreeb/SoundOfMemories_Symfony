<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260327133000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute trois fonds de section administrables dans SiteSettings';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('site_settings');
        foreach (['section_background_primary', 'section_background_secondary', 'section_background_tertiary'] as $column) {
            if (!$table->hasColumn($column)) {
                $table->addColumn($column, 'string', ['length' => 255, 'notnull' => false]);
            }
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('site_settings');
        foreach (['section_background_primary', 'section_background_secondary', 'section_background_tertiary'] as $column) {
            if ($table->hasColumn($column)) {
                $table->dropColumn($column);
            }
        }
    }
}
