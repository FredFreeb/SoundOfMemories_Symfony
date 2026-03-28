<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260322110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute de nouveau la session Stripe pour permettre Stripe et Mollie en parallele';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "order" ADD stripe_checkout_session_id VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "order" DROP COLUMN stripe_checkout_session_id');
    }
}
