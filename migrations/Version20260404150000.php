<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260404150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute des modules éditoriaux initiaux pour l’accueil et la gallery.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
INSERT INTO editorial_module
    (page_key, section_key, module_type, eyebrow, title, lead_text, body_text, image_path, background_image_path, meta_primary, meta_secondary, meta_tertiary, accent_tone, layout_preset, background_slot, cta_label, cta_url, position, is_published, created_at, updated_at)
SELECT
    'home',
    'intro',
    'story_block',
    'Base arrière',
    'Une fan base pensée comme un campement live, pas comme une simple vitrine.',
    'Le site doit autant accueillir les fans, les dates, les visuels et le merch que raconter une présence de groupe.',
    'Sound Of Memories se déploie ici comme une base arrière : du merch officiel, des archives visuelles, des concerts à suivre et des modules éditoriaux qui donnent plus de souffle au récit du groupe.

L’idée n’est pas de faire une boutique plaquée sur un site vitrine, mais un territoire cohérent, administrable et capable de faire vivre la scène, les objets et la mémoire du groupe dans la même expérience.',
    'uploads/optimized/legacy/gal13.jpg',
    NULL,
    'Fan base',
    'Merch officiel',
    'Concerts et archives',
    'ash',
    'split',
    'section-bg-1',
    NULL,
    NULL,
    10,
    1,
    NOW(),
    NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM editorial_module WHERE page_key = 'home' AND section_key = 'intro'
)
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO editorial_module
    (page_key, section_key, module_type, eyebrow, title, lead_text, body_text, image_path, background_image_path, meta_primary, meta_secondary, meta_tertiary, accent_tone, layout_preset, background_slot, cta_label, cta_url, position, is_published, created_at, updated_at)
SELECT
    'home',
    'press',
    'press_clippings',
    'Press',
    'Ce que la presse retient du groupe, lu comme une revue rock.',
    'Un cadre éditorial plus assumé pour accueillir articles, reviews, citations et retours de terrain.',
    NULL,
    NULL,
    NULL,
    NULL,
    NULL,
    NULL,
    'ocean',
    'centered',
    'section-bg-3',
    'Voir la galerie',
    '/gallery',
    90,
    1,
    NOW(),
    NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM editorial_module WHERE page_key = 'home' AND section_key = 'press'
)
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO editorial_module
    (page_key, section_key, module_type, eyebrow, title, lead_text, body_text, image_path, background_image_path, meta_primary, meta_secondary, meta_tertiary, accent_tone, layout_preset, background_slot, cta_label, cta_url, position, is_published, created_at, updated_at)
SELECT
    'gallery',
    'intro',
    'story_block',
    'Visual archive',
    'Une galerie qui mélange clip, album, scène et mémoire visuelle.',
    'Le groupe ne se raconte pas uniquement par ses morceaux, mais aussi par ses images, ses textures live et ses traces.',
    'La gallery rassemble plusieurs couches du projet : les clips, les albums à écouter dans la page, les images de scène et les portraits plus cinématographiques.

Elle doit fonctionner comme une archive vivante, entre matière live, iconographie de groupe et souvenirs qui peuvent encore évoluer depuis l’admin.',
    'uploads/optimized/legacy/gal18.jpg',
    NULL,
    'Clips',
    'Albums',
    'Archives visuelles',
    'forest',
    'split',
    'section-bg-1',
    NULL,
    NULL,
    10,
    1,
    NOW(),
    NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM editorial_module WHERE page_key = 'gallery' AND section_key = 'intro'
)
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO editorial_module
    (page_key, section_key, module_type, eyebrow, title, lead_text, body_text, image_path, background_image_path, meta_primary, meta_secondary, meta_tertiary, accent_tone, layout_preset, background_slot, cta_label, cta_url, position, is_published, created_at, updated_at)
SELECT
    'gallery',
    'memory',
    'story_block',
    'Memory line',
    'Les photos doivent documenter autant qu’elles projettent un imaginaire.',
    'Entre concert, backstage, lumière et tension, la galerie photo doit rester immersive sans devenir un simple dossier d’images.',
    'Chaque image doit pouvoir jouer à deux niveaux : document de scène d’un côté, matière visuelle presque éditoriale de l’autre.

Le plein écran garde ce côté plus immersif, pendant que l’admin peut continuer à enrichir la sélection au fil des concerts et des archives retrouvées.',
    'uploads/optimized/legacy/gal15.jpg',
    NULL,
    'Concert',
    'Backstage',
    'Mémoire visuelle',
    'sand',
    'split',
    'section-bg-2',
    NULL,
    NULL,
    80,
    1,
    NOW(),
    NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM editorial_module WHERE page_key = 'gallery' AND section_key = 'memory'
)
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM editorial_module WHERE page_key = 'gallery' AND section_key IN ('intro', 'memory')");
        $this->addSql("DELETE FROM editorial_module WHERE page_key = 'home' AND section_key IN ('intro', 'press')");
    }
}
