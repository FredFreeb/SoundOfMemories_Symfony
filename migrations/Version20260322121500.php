<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260322121500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute trois visuels admin-manageables pour la presentation de la home';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE site_settings ADD home_overview_image_one VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE site_settings ADD home_overview_image_two VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE site_settings ADD home_overview_image_three VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE site_settings DROP COLUMN home_overview_image_one');
        $this->addSql('ALTER TABLE site_settings DROP COLUMN home_overview_image_two');
        $this->addSql('ALTER TABLE site_settings DROP COLUMN home_overview_image_three');
    }
}
