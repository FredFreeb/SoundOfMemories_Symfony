# Sound Of Memories Fan Base

Refonte Symfony/Docker du vieux site Sound Of Memories avec:

- un front mobile-first
- un back-office EasyAdmin
- une gestion du merchandising
- une gestion des dates de concert
- une base prête à être transférée sur VPS

## Adresse locale du projet

Le projet est prévu sur:

- `http://localhost:8088`

Autres ports locaux de cette stack:

- MariaDB: `127.0.0.1:3307`
- Mailpit SMTP: `127.0.0.1:1026`
- Mailpit web: `http://localhost:8026`

Le port est défini dans [compose.yaml](/Users/papounet/Coding/SOF_Website/SoundOfMemories_Symfony/compose.yaml) via `HTTP_PORT`, avec `8088` par défaut pour ne pas entrer en conflit avec votre autre site sur `http://localhost:8088`.

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

## Déploiement VPS

Le projet reste compatible avec un déploiement simple sur VPS Docker:

1. envoyer le code
2. configurer les variables d’environnement de production
3. lancer `docker compose up -d --build`
4. exécuter les migrations
5. créer l’admin

Pour la prod, pensez à définir au minimum:

```dotenv
APP_ENV=prod
APP_SECRET=change-me
HTTP_PORT=8088
DATABASE_URL=mysql://soundofmemories:soundofmemories@database:3306/soundofmemories?serverVersion=10.11.2-MariaDB&charset=utf8mb4
MARIADB_DATABASE=soundofmemories
MARIADB_USER=soundofmemories
MARIADB_PASSWORD=motdepassefort
MARIADB_ROOT_PASSWORD=encoreplusfort
```
