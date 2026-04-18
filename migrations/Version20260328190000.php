<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260328190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les liens de plateformes musicales dans les paramètres visuels du site.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE site_settings ADD COLUMN soundcloud_url VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE site_settings ADD COLUMN spotify_url VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE site_settings ADD COLUMN apple_music_url VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE site_settings ADD COLUMN youtube_music_url VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigration('Cette migration SQLite ajoute des colonnes sans rollback propre prévu.');
    }
}
