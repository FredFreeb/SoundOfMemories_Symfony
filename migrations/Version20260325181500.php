<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260325181500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les presets de style de containers pour la home et l identite visuelle';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE site_settings ADD home_intro_style_preset VARCHAR(40) DEFAULT \'type-2\' NOT NULL');
        $this->addSql('ALTER TABLE site_settings ADD home_archive_cta_style_preset VARCHAR(40) DEFAULT \'type-1\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE site_settings DROP home_intro_style_preset');
        $this->addSql('ALTER TABLE site_settings DROP home_archive_cta_style_preset');
    }
}
