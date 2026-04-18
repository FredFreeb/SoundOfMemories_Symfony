<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260327102500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les slides hero multiples dans SiteSettings';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('site_settings');
        foreach (['home_hero_slide_one', 'home_hero_slide_two', 'home_hero_slide_three', 'home_hero_slide_four'] as $column) {
            if (!$table->hasColumn($column)) {
                $table->addColumn($column, 'string', ['length' => 255, 'notnull' => false]);
            }
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('site_settings');
        foreach (['home_hero_slide_one', 'home_hero_slide_two', 'home_hero_slide_three', 'home_hero_slide_four'] as $column) {
            if ($table->hasColumn($column)) {
                $table->dropColumn($column);
            }
        }
    }
}
