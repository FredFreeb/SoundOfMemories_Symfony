<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260325173000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute un avatar Google en fallback pour les comptes relies a Google';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD google_avatar_url VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" DROP COLUMN google_avatar_url');
    }
}
