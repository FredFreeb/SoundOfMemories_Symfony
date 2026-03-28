# Animations produits

Je garde les animations communes dans [templates/layouts/animations/_product_effects.html.twig](/Users/papounet/Coding/expeditionsMysterieuses/symfony-base/templates/layouts/animations/_product_effects.html.twig).

## Principe

- chaque produit choisit une cle dans l'admin: `manoir`, `tresor`, `spectral`
- le front applique alors une classe du type `animation-manoir`
- un champ `animationCss` permet d'ajouter un ajustement CSS propre a un produit

## Ajouter une nouvelle animation

1. ajouter un bloc `.animation-ma-cle` dans `templates/layouts/animations/_product_effects.html.twig`
2. ajouter la nouvelle option dans `ProductCrudController::ANIMATION_CHOICES`
3. choisir cette cle dans le produit depuis l'admin

## Exemple

- `manoir`: effet porte qui s'ouvre et chauves-souris
- `tresor`: bulles et halo sous-marin
- `spectral`: contour mystique anime
