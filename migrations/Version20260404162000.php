<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260404162000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute des modules éditoriaux initiaux pour les pages concerts et boutique.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
INSERT INTO editorial_module
    (page_key, section_key, module_type, eyebrow, title, lead_text, body_text, image_path, background_image_path, meta_primary, meta_secondary, meta_tertiary, accent_tone, layout_preset, background_slot, cta_label, cta_url, position, is_published, created_at, updated_at)
SELECT
    'concerts',
    'intro',
    'concert_chronicle',
    'Live chronicle',
    'Chaque date doit se lire comme une ligne de route, pas comme un simple listing.',
    'Le module live sert à poser le ton, expliquer le rythme du groupe sur scène et donner un cadre plus narratif à la page concerts.',
    'Cette page ne sert pas seulement à empiler des dates. Elle doit aussi raconter la dynamique live du groupe, l’état des concerts à venir et le type d’expérience que les fans vont retrouver sur place.

Quand les affiches, les statuts et les liens de billetterie sont bien cadrés, la page devient à la fois pratique et plus désirable.',
    NULL,
    NULL,
    'Dates confirmées',
    'Billetterie directe',
    'Affiches gérées en admin',
    'ember',
    'split',
    'section-bg-1',
    'Voir les dates',
    '#dates-live',
    10,
    1,
    NOW(),
    NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM editorial_module WHERE page_key = 'concerts' AND section_key = 'intro'
)
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO editorial_module
    (page_key, section_key, module_type, eyebrow, title, lead_text, body_text, image_path, background_image_path, meta_primary, meta_secondary, meta_tertiary, accent_tone, layout_preset, background_slot, cta_label, cta_url, position, is_published, created_at, updated_at)
SELECT
    'shop',
    'story',
    'merch_story_block',
    'Merch system',
    'Un catalogue qui doit donner envie d’acheter, mais aussi tenir la route dans le temps.',
    'La boutique ne doit pas seulement montrer des produits: elle doit exprimer un drop, une qualité perçue, une logique de stock et une identité de groupe.',
    'Le merchandising Sound Of Memories peut vivre comme une extension de la scène: pièces textiles, posters, objets de fan base et éditions qui racontent quelque chose du groupe.

L’admin garde la main sur le stock, les promos, les variantes et l’ordre d’affichage, pendant que le front reste plus désirable, plus clair et plus proche d’un vrai merch store live.',
    'uploads/optimized/legacy/bannerToDel.jpg',
    NULL,
    'Stock piloté',
    'Promotions planifiables',
    'Variantes et visuels',
    'ash',
    'poster',
    'section-bg-3',
    'Entrer dans le catalogue',
    '#catalogue',
    20,
    1,
    NOW(),
    NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM editorial_module WHERE page_key = 'shop' AND section_key = 'story'
)
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO editorial_module_item
    (module_id, eyebrow, title, subtitle, body_text, image_path, meta_primary, meta_secondary, link_label, link_url, position, is_published, created_at, updated_at)
SELECT module.id, seeded.eyebrow, seeded.title, seeded.subtitle, seeded.body_text, NULL, seeded.meta_primary, seeded.meta_secondary, seeded.link_label, seeded.link_url, seeded.position, 1, NOW(), NOW()
FROM editorial_module module
JOIN (
    SELECT 'concerts' AS page_key, 'intro' AS section_key, 'Cadence' AS eyebrow, 'Des dates qui restent lisibles' AS title, 'Avant / maintenant / passé' AS subtitle, 'Le statut doit immédiatement dire si le concert est à venir, déjà joué ou encore en préparation.' AS body_text, 'Planning' AS meta_primary, 'Lecture immédiate' AS meta_secondary, NULL AS link_label, NULL AS link_url, 10 AS position
    UNION ALL
    SELECT 'concerts', 'intro', 'Billetterie', 'Un accès rapide au prochain move', 'Lien direct', 'Quand il y a une billeterie, elle doit être visible sans noyer la fiche du concert.' , 'CTA direct', 'Conversion live', NULL, NULL, 20
    UNION ALL
    SELECT 'concerts', 'intro', 'Affiche', 'Un visuel qui fixe la mémoire de la date', 'Poster admin', 'Chaque concert peut avoir sa vignette d’affiche pour renforcer la dimension tournée / archive.' , 'Backstage ready', 'Visuel géré', NULL, NULL, 30
    UNION ALL
    SELECT 'shop', 'story', 'Textile', 'Pièces prêtes pour la scène ou le quotidien', 'Coupe, matière, taille', 'Les pièces textiles doivent être rassurantes et lisibles, sans perdre le côté groupe.' , 'Variantes', 'Guide de choix', NULL, NULL, 10
    UNION ALL
    SELECT 'shop', 'story', 'Collectible', 'Posters, pins et formats à collectionner', 'Objets de fan base', 'Les petits objets doivent pouvoir vivre comme des produits d’appel, mais aussi comme pièces de collection.' , 'Poster / pin', 'Edition visuelle', NULL, NULL, 20
    UNION ALL
    SELECT 'shop', 'story', 'Pilotage', 'Une boutique pensée pour être tenue dans le temps', 'Admin merch', 'Prix, stock, promotions et mises en avant restent administrables sans toucher au code.' , 'Back-office', 'Gestion durable', NULL, NULL, 30
) seeded
    ON seeded.page_key = module.page_key
   AND seeded.section_key = module.section_key
LEFT JOIN editorial_module_item existing
    ON existing.module_id = module.id
   AND existing.position = seeded.position
WHERE existing.id IS NULL
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE items FROM editorial_module_item items INNER JOIN editorial_module module ON items.module_id = module.id WHERE module.page_key = 'concerts' AND module.section_key = 'intro'");
        $this->addSql("DELETE items FROM editorial_module_item items INNER JOIN editorial_module module ON items.module_id = module.id WHERE module.page_key = 'shop' AND module.section_key = 'story'");
        $this->addSql("DELETE FROM editorial_module WHERE page_key = 'concerts' AND section_key = 'intro'");
        $this->addSql("DELETE FROM editorial_module WHERE page_key = 'shop' AND section_key = 'story'");
    }
}
