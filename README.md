# Sound Of Memories

Refonte Symfony/Docker du vieux site Sound Of Memories avec:

- un front mobile-first
- un back-office EasyAdmin
- une gestion du merchandising
- une gestion des dates de concert
- une base prête à être transférée sur VPS

## Adresse locale du projet

Le projet canonique est désormais:

- `/Users/papounet/Coding/SOM_Website/SoundOfMemories_Symfony`

Adresse locale:

- `http://127.0.0.1:8050`

Autres ports locaux de cette stack:

- MariaDB: `127.0.0.1:13307`
- Mailpit: désactivé par défaut pour alléger Docker Desktop

Le port est défini dans [compose.yaml](/Users/papounet/Coding/SOM_Website/SoundOfMemories_Symfony/compose.yaml) via `HTTP_PORT`, avec `8050` par défaut.

## Base de dev actuelle

En local hors Docker, la base utilisée par défaut est:

- `var/som_dev.db`

Cela permet de travailler vite sans toucher à une ancienne base copiée depuis un autre projet.

## Commandes utiles

```bash
bin/docker-up
bin/docker-init-db
php bin/console app:load-demo-catalog
php bin/console app:create-admin-user admin@example.com motdepasse "Nom Admin"
```

Pour un volume MariaDB neuf en Docker, on initialise le schéma avec `bin/docker-init-db`.
Les anciennes migrations du projet ont été générées à l'époque du mode SQLite local et ne sont pas directement rejouables telles quelles sur MariaDB.

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
4. initialiser le schéma avec `bin/docker-init-db` sur une base MariaDB neuve
5. créer l’admin

Pour la prod, pensez à définir au minimum:

```dotenv
APP_ENV=prod
APP_SECRET=change-me
HTTP_PORT=8050
DATABASE_URL=mysql://soundofmemories:soundofmemories@database:3306/soundofmemories?serverVersion=10.11.2-MariaDB&charset=utf8mb4
MARIADB_DATABASE=soundofmemories
MARIADB_USER=soundofmemories
MARIADB_PASSWORD=motdepassefort
MARIADB_ROOT_PASSWORD=encoreplusfort
```
