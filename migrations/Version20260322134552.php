<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260322134552 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE customer_conversation (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, type VARCHAR(40) NOT NULL, subject VARCHAR(180) NOT NULL, customer_name VARCHAR(180) NOT NULL, customer_email VARCHAR(180) NOT NULL, status VARCHAR(40) NOT NULL, has_unread_for_admin BOOLEAN NOT NULL, created_at DATETIME NOT NULL, last_message_at DATETIME NOT NULL, customer_account_id INTEGER DEFAULT NULL, order_ref_id INTEGER DEFAULT NULL, CONSTRAINT FK_8456E5F066A25B38 FOREIGN KEY (customer_account_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_8456E5F0E238517C FOREIGN KEY (order_ref_id) REFERENCES "order" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_8456E5F066A25B38 ON customer_conversation (customer_account_id)');
        $this->addSql('CREATE INDEX IDX_8456E5F0E238517C ON customer_conversation (order_ref_id)');
        $this->addSql('CREATE TABLE customer_conversation_message (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, author_type VARCHAR(20) NOT NULL, author_name VARCHAR(120) NOT NULL, body CLOB NOT NULL, created_at DATETIME NOT NULL, conversation_id INTEGER NOT NULL, CONSTRAINT FK_F2B7B1409AC0396 FOREIGN KEY (conversation_id) REFERENCES customer_conversation (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_F2B7B1409AC0396 ON customer_conversation_message (conversation_id)');
        $this->addSql('CREATE TABLE mailing_campaign (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(160) NOT NULL, subject VARCHAR(180) NOT NULL, preview_text VARCHAR(255) DEFAULT NULL, content CLOB NOT NULL, audience_label VARCHAR(120) DEFAULT NULL, status VARCHAR(40) NOT NULL, created_at DATETIME NOT NULL, scheduled_at DATETIME DEFAULT NULL, sent_at DATETIME DEFAULT NULL)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__product_gallery_image AS SELECT id, product_id, image_path, alt_text, position FROM product_gallery_image');
        $this->addSql('DROP TABLE product_gallery_image');
        $this->addSql('CREATE TABLE product_gallery_image (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, product_id INTEGER NOT NULL, image_path VARCHAR(255) NOT NULL, alt_text VARCHAR(180) DEFAULT NULL, position INTEGER NOT NULL, CONSTRAINT FK_12FD0FB94584665A FOREIGN KEY (product_id) REFERENCES product (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO product_gallery_image (id, product_id, image_path, alt_text, position) SELECT id, product_id, image_path, alt_text, position FROM __temp__product_gallery_image');
        $this->addSql('DROP TABLE __temp__product_gallery_image');
        $this->addSql('CREATE INDEX IDX_FB4DC88E4584665A ON product_gallery_image (product_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE customer_conversation');
        $this->addSql('DROP TABLE customer_conversation_message');
        $this->addSql('DROP TABLE mailing_campaign');
        $this->addSql('CREATE TEMPORARY TABLE __temp__product_gallery_image AS SELECT id, image_path, alt_text, position, product_id FROM product_gallery_image');
        $this->addSql('DROP TABLE product_gallery_image');
        $this->addSql('CREATE TABLE product_gallery_image (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, image_path VARCHAR(255) NOT NULL, alt_text VARCHAR(180) DEFAULT NULL, position INTEGER NOT NULL, product_id INTEGER NOT NULL, CONSTRAINT FK_FB4DC88E4584665A FOREIGN KEY (product_id) REFERENCES product (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO product_gallery_image (id, image_path, alt_text, position, product_id) SELECT id, image_path, alt_text, position, product_id FROM __temp__product_gallery_image');
        $this->addSql('DROP TABLE __temp__product_gallery_image');
        $this->addSql('CREATE INDEX IDX_12FD0FB94584665A ON product_gallery_image (product_id)');
    }
}
