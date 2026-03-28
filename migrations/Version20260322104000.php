<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260322104000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Renomme la reference de paiement Stripe en identifiant Mollie';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "order" RENAME COLUMN stripe_checkout_session_id TO mollie_payment_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "order" RENAME COLUMN mollie_payment_id TO stripe_checkout_session_id');
    }
}
