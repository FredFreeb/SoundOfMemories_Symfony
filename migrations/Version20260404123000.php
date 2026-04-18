<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260404123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crée les modules éditoriaux administrables et initialise la page Le groupe.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE editorial_module (id INT AUTO_INCREMENT NOT NULL, page_key VARCHAR(64) NOT NULL, section_key VARCHAR(64) NOT NULL, module_type VARCHAR(64) NOT NULL, eyebrow VARCHAR(180) DEFAULT NULL, title VARCHAR(255) NOT NULL, lead_text LONGTEXT DEFAULT NULL, body_text LONGTEXT DEFAULT NULL, image_path VARCHAR(255) DEFAULT NULL, background_image_path VARCHAR(255) DEFAULT NULL, meta_primary VARCHAR(180) DEFAULT NULL, meta_secondary VARCHAR(180) DEFAULT NULL, meta_tertiary VARCHAR(180) DEFAULT NULL, accent_tone VARCHAR(32) NOT NULL, layout_preset VARCHAR(32) NOT NULL, background_slot VARCHAR(32) NOT NULL, cta_label VARCHAR(120) DEFAULT NULL, cta_url VARCHAR(255) DEFAULT NULL, position INT NOT NULL, is_published TINYINT(1) NOT NULL DEFAULT 1, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_EDITORIAL_MODULE_PAGE_SECTION (page_key, section_key), INDEX IDX_EDITORIAL_MODULE_PAGE_PUBLISHED (page_key, is_published), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE editorial_module_item (id INT AUTO_INCREMENT NOT NULL, module_id INT NOT NULL, eyebrow VARCHAR(180) DEFAULT NULL, title VARCHAR(255) NOT NULL, subtitle VARCHAR(180) DEFAULT NULL, body_text LONGTEXT DEFAULT NULL, image_path VARCHAR(255) DEFAULT NULL, meta_primary VARCHAR(180) DEFAULT NULL, meta_secondary VARCHAR(180) DEFAULT NULL, link_label VARCHAR(120) DEFAULT NULL, link_url VARCHAR(255) DEFAULT NULL, position INT NOT NULL, is_published TINYINT(1) NOT NULL DEFAULT 1, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_EDITORIAL_MODULE_ITEM_MODULE (module_id), PRIMARY KEY(id), CONSTRAINT FK_EDITORIAL_MODULE_ITEM_MODULE FOREIGN KEY (module_id) REFERENCES editorial_module (id) ON DELETE CASCADE) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql(<<<'SQL'
INSERT INTO editorial_module
    (id, page_key, section_key, module_type, eyebrow, title, lead_text, body_text, image_path, background_image_path, meta_primary, meta_secondary, meta_tertiary, accent_tone, layout_preset, background_slot, cta_label, cta_url, position, is_published, created_at, updated_at)
VALUES
    (1, 'about', 'hero', 'manifesto_hero', 'som-band', 'Death mélodique, énergie live et mémoire sonore.', 'Depuis 2010, Sound Of Memories développe une identité forgée entre riffs heavy / thrash, base death melodic old school et vraie ambition de scène.', NULL, 'uploads/legacy/about.JPG', 'uploads/legacy/about.JPG', 'Essonne', 'Death mélodique', 'Depuis 2010', 'ash', 'poster', 'section-bg-none', NULL, NULL, 10, 1, NOW(), NOW()),
    (2, 'about', 'story', 'story_block', 'Notre histoire', 'Un groupe construit entre studio, réseau et scènes métal.', 'Une trajectoire forgée dans les studios, les salles et les réseaux du metal français.', 'Créé en 2010 à Paris par Alain à la guitare, Lucho à la guitare et Fabien à la basse, Sound of Memories se définit par un mélange de riffs heavy / thrash metal sur une base death melodic old school.

C’est en 2012, avec l’arrivée de Nacim à la batterie puis de Flo au chant l’année suivante, que le combo se fixe des objectifs plus sérieux et concrétise ses efforts en enregistrant son premier E.P. Living Circles au studio Zoe-H en septembre 2013.

Suite à des retours plus que favorables, Sound of Memories intègre Army Of One pour la promotion et le touring, développe son réseau via Thanatos Production et défend ainsi ses compositions sur de nombreuses scènes comme le PYHC Fest, le Covent Garden d’Eragny ou encore La Boule Noire.

En parallèle, le groupe poursuit son travail de composition et rentre en studio en février 2015 afin d’enregistrer son premier album. Julien Delsol prend en charge la production d’un opus plus sombre, plus complexe et plus ambitieux, pendant que la signature sur le label Finisterian Dead End confirme la volonté du groupe de franchir un cap sur la scène métal nationale.

To Deliverance est disponible dans tous les bacs de France et d’Angleterre depuis le 2 novembre 2015. Le groupe donne alors rendez-vous aux amateurs de headbang sur les routes de France et de Navarre pour défendre une musique énergique, brutale et mélodique.', 'uploads/legacy/about.JPG', NULL, 'Essonne / Paris', 'Heavy · Thrash · Death melodic', 'Scène, studio, fan base', 'ember', 'split', 'section-bg-1', NULL, NULL, 20, 1, NOW(), NOW()),
    (3, 'about', 'lineup', 'lineup_grid', 'Line-up', 'Un line-up resserré, pensé pour la scène.', 'Une architecture humaine simple, mais taillée pour l’impact live.', NULL, NULL, NULL, NULL, NULL, NULL, 'forest', 'centered', 'section-bg-2', NULL, NULL, 30, 1, NOW(), NOW()),
    (4, 'about', 'discography', 'release_grid', 'Discographie', 'Deux sorties pour fixer l’identité du groupe.', 'Des sorties qui ont installé le langage du groupe entre tension mélodique et frontalité metal.', NULL, NULL, NULL, NULL, NULL, NULL, 'ocean', 'strip', 'section-bg-3', NULL, NULL, 40, 1, NOW(), NOW())
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO editorial_module_item
    (module_id, eyebrow, title, subtitle, body_text, image_path, meta_primary, meta_secondary, link_label, link_url, position, is_published, created_at, updated_at)
VALUES
    (3, 'Guitare', 'Alain', 'Co-fondateur', 'Colonne vertébrale du son et tension heavy / thrash dans les arrangements.', NULL, NULL, NULL, NULL, NULL, 10, 1, NOW(), NOW()),
    (3, 'Guitare', 'Lucho', 'Co-fondateur', 'Contrepoint mélodique et équilibre entre densité et respiration.', NULL, NULL, NULL, NULL, NULL, 20, 1, NOW(), NOW()),
    (3, 'Basse', 'Fabien', 'Co-fondateur', 'Fondation grave, impulsion rythmique et ancrage du projet depuis l’origine.', NULL, NULL, NULL, NULL, NULL, 30, 1, NOW(), NOW()),
    (3, 'Batterie', 'Nacim', NULL, 'Impact live et précision old school au service du relief des morceaux.', NULL, NULL, NULL, NULL, NULL, 40, 1, NOW(), NOW()),
    (3, 'Chant', 'Flo', NULL, 'Présence frontale, brutalité maîtrisée et relief scénique.', NULL, NULL, NULL, NULL, NULL, 50, 1, NOW(), NOW()),
    (4, 'EP · 2013', 'Living Circles', 'Premier basculement discographique', 'Premier E.P. enregistré au studio Zoe-H, point de bascule entre le projet de départ et une ambition de groupe plus affirmée.', 'uploads/legacy/Living.jpg', 'Studio Zoe-H', 'Septembre 2013', NULL, NULL, 10, 1, NOW(), NOW()),
    (4, 'Album · 2015', 'To Deliverance', 'Premier album studio', 'Premier album du groupe, produit par Julien Delsol et porté par une écriture plus sombre, plus dense et plus ambitieuse.', 'uploads/legacy/Deliv.jpg', 'Finisterian Dead End', '2 novembre 2015', NULL, NULL, 20, 1, NOW(), NOW())
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE editorial_module_item');
        $this->addSql('DROP TABLE editorial_module');
    }
}
