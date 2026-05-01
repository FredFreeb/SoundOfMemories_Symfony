# Sound Of Memories Fan Base

Refonte Symfony/Docker du vieux site Sound Of Memories avec:

- un front mobile-first
- un back-office EasyAdmin
- une gestion du merchandising
- une gestion des dates de concert
- une base prête à être transférée sur VPS

## Base de dev actuelle

En local hors Docker, la base utilisée par défaut est:

- `var/som_dev.db`

Cela permet de travailler vite sans toucher à une ancienne base copiée depuis un autre projet.

## Commandes utiles

```bash
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console app:load-demo-catalog
php bin/console app:create-admin-user admin@example.com motdepasse "Nom Admin"
docker compose up -d --build
```

## Ce qui est déjà prêt

- page d’accueil Sound Of Memories
- page merchandising
- page concerts
- admin `backstage` sur `/backstage`
- visuels legacy copiés en local dans `public/uploads/legacy/`
- seed de démonstration pour produits, concerts et identité du site

