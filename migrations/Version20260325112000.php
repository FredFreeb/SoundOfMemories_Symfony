<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260325112000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la verification d email aux comptes utilisateurs';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD is_verified BOOLEAN NOT NULL DEFAULT 1');
        $this->addSql('ALTER TABLE user ADD verified_at DATETIME DEFAULT NULL');
        $this->addSql("UPDATE user SET is_verified = 1, verified_at = CURRENT_TIMESTAMP WHERE verified_at IS NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP is_verified');
        $this->addSql('ALTER TABLE user DROP verified_at');
    }
}
