<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260326102000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les presets de containers pour la page Qui sommes-nous';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE site_settings ADD about_primary_style_preset VARCHAR(40) DEFAULT \'type-1\' NOT NULL');
        $this->addSql('ALTER TABLE site_settings ADD about_secondary_style_preset VARCHAR(40) DEFAULT \'type-2\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE site_settings DROP about_primary_style_preset');
        $this->addSql('ALTER TABLE site_settings DROP about_secondary_style_preset');
    }
}
