# Boxtal

Configuration locale a placer dans `.env.local` :

```dotenv
BOXTAL_ENABLED=1
BOXTAL_TEST_MODE=1
BOXTAL_USER=...
BOXTAL_PASSWORD=...
BOXTAL_CONTENT_CODE=...
BOXTAL_FROM_COUNTRY=CZ
BOXTAL_FROM_POSTAL_CODE=15500
BOXTAL_DEFAULT_PARCEL_WEIGHT_GRAMS=1200
BOXTAL_EXTRA_ITEM_WEIGHT_GRAMS=350
BOXTAL_PARCEL_LENGTH_CM=34
BOXTAL_PARCEL_WIDTH_CM=24
BOXTAL_PARCEL_HEIGHT_CM=9
```

Notes utiles :

- `BOXTAL_TEST_MODE=1` permet d'utiliser le serveur de test `https://test.envoimoinscher.com/api/v1`.
- `BOXTAL_CONTENT_CODE` correspond a la categorie de contenu Boxtal a utiliser pour la cotation.
- `BOXTAL_FROM_COUNTRY` et `BOXTAL_FROM_POSTAL_CODE` servent de profil expéditeur par defaut.
- Le projet utilise pour l'instant un profil colis global pour tous les produits.
- Quand Boxtal n'est pas active, le checkout degrade vers des tarifs estimatifs internes pour garder la maquette exploitable.

Sources officielles :

- Getting started API v1 : https://developer.boxtal.com/fr/en/apiv1/guide/getting-started-api-v1
- Quote API v1 : https://developer.boxtal.com/fr/en/apiv1/guide/quote-api-v1
- Test mode API v1 : https://developer.boxtal.com/fr/en/apiv1/guide/sandbox-api-v1
- Guide API v1 : https://developer.boxtal.com/fr/en/apiv1/guide/
