# Architecture de base

## Pourquoi Symfony ici

Symfony est un bon choix pour ce projet si l’objectif est d’avoir un code plus structure que WordPress, avec un meilleur controle sur les routes, la securite, les donnees et les evolutions metier.

## Separation front / back

Le projet reste un seul noyau Symfony, mais avec une separation nette :

- front : `src/Controller/Store/` et `templates/store/`
- back-office : `src/Controller/Admin/` et `templates/admin/`
- layouts dedies : `templates/layouts/store.html.twig` et `templates/layouts/admin.html.twig`

Cette approche garde un seul projet a deployer, tout en separant les responsabilites et la mise en page.

## Gestion des commandes

Le modele actuel prepare :

- la commande
- les lignes de commande
- le statut de paiement
- la reference de paiement

Cela suffit pour brancher ensuite Mollie ou un flux de paiement personnalise.

## Gestion du stock

Le stock est prevu directement dans `Product`. A moyen terme, si tu veux un vrai historique de mouvements, on pourra ajouter une entite `StockMovement`.

## FAQ et contenu

La FAQ est volontairement modelisee a part. C’est une bonne habitude pour ne pas melanger produit et contenu editorial.

## Deploiement

Le plus simple pour Hostinger est un VPS avec Docker Compose :

- un conteneur `php`
- un conteneur `nginx`
- un conteneur `database`

Ensuite, tu pourras ajouter du HTTPS avec un reverse proxy ou la configuration de ton serveur.
