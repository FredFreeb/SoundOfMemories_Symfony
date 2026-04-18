Configuration locale des intégrations
=====================================

Tous les secrets locaux se règlent dans :

- [\.env.local](/Users/papounet/Coding/SOM_Website/SoundOfMemories_Symfony/.env.local)

Le projet lit actuellement ces variables :

```dotenv
DEFAULT_URI=http://127.0.0.1:8081

MAILER_DSN=sendmail://default?command=/usr/bin/env%20php%20/Users/papounet/Coding/SOM_Website/SoundOfMemories_Symfony/bin/dev-sendmail.php%20-t

MOLLIE_API_KEY=

STRIPE_PUBLIC_KEY=
STRIPE_SECRET_KEY=
STRIPE_WEBHOOK_SECRET=

OAUTH_GOOGLE_ENABLED=0
OAUTH_GOOGLE_CLIENT_ID=
OAUTH_GOOGLE_CLIENT_SECRET=

OAUTH_APPLE_ENABLED=0
OAUTH_APPLE_CLIENT_ID=
OAUTH_APPLE_TEAM_ID=
OAUTH_APPLE_KEY_FILE_ID=
OAUTH_APPLE_KEY_FILE_PATH=/Users/papounet/Coding/SOM_Website/SoundOfMemories_Symfony/config/oauth/keys/AuthKey_XXXX.p8
```

Google
------

Routes du projet :

- connexion : `http://127.0.0.1:8081/connexion/google`
- retour OAuth : `http://127.0.0.1:8081/connexion/google/check`

Valeurs à récupérer dans Google Cloud :

- `OAUTH_GOOGLE_CLIENT_ID`
- `OAUTH_GOOGLE_CLIENT_SECRET`

Si tu ne vois pas encore ces valeurs, il manque souvent une étape : il faut créer un vrai client OAuth web dans Google Cloud.

Chemin le plus simple :

1. Google Cloud Console
2. choisis ton projet
3. `Google Auth Platform` puis `Clients`
4. ou `APIs & Services` puis `Credentials`
5. `Create credentials`
6. `OAuth client ID`
7. type : `Web application`

À renseigner :

- nom : par exemple `Sound Of Memories Local`
- Authorized JavaScript origins :
  - `http://127.0.0.1:8081`
  - `http://localhost:8081`
- Authorized redirect URIs :
  - `http://127.0.0.1:8081/connexion/google/check`
  - `http://localhost:8081/connexion/google/check`

Google affichera ensuite :

- le `Client ID`
- le `Client Secret`

Une fois récupérées :

```dotenv
OAUTH_GOOGLE_ENABLED=1
OAUTH_GOOGLE_CLIENT_ID=xxxxxxxxxxxx-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx.apps.googleusercontent.com
OAUTH_GOOGLE_CLIENT_SECRET=GOCSPX-xxxxxxxxxxxxxxxxxxxxxxxx
```

Apple
-----

Routes du projet :

- connexion : `https://ton-domaine.tld/connexion/apple`
- retour OAuth : `https://ton-domaine.tld/connexion/apple/check`

Important : Apple demande un `redirect_uri` en HTTPS avec nom de domaine. Un IP, `localhost` ou `127.0.0.1` ne conviennent pas pour le flux web.

Valeurs à récupérer dans Apple Developer :

- `OAUTH_APPLE_CLIENT_ID` : ton `Services ID`
- `OAUTH_APPLE_TEAM_ID`
- `OAUTH_APPLE_KEY_FILE_ID`
- `OAUTH_APPLE_KEY_FILE_PATH` : chemin absolu vers la clé `.p8`

Dépose la clé téléchargée ici :

- [keys](/Users/papounet/Coding/SOM_Website/SoundOfMemories_Symfony/config/oauth/keys)

Exemple :

```dotenv
OAUTH_APPLE_ENABLED=1
OAUTH_APPLE_CLIENT_ID=com.expeditionmystere.web
OAUTH_APPLE_TEAM_ID=ABCD123456
OAUTH_APPLE_KEY_FILE_ID=9XYZ123ABC
OAUTH_APPLE_KEY_FILE_PATH=/Users/papounet/Coding/SOM_Website/SoundOfMemories_Symfony/config/oauth/keys/AuthKey_9XYZ123ABC.p8
```

Stripe
------

Routes du projet :

- checkout : `http://127.0.0.1:8081/commande`
- webhook Stripe : `http://127.0.0.1:8081/commande/webhook/stripe`

Valeurs à récupérer dans Stripe :

- `STRIPE_PUBLIC_KEY` : commence par `pk_test_` ou `pk_live_`
- `STRIPE_SECRET_KEY` : commence par `sk_test_` ou `sk_live_`
- `STRIPE_WEBHOOK_SECRET` : commence par `whsec_`

Exemple :

```dotenv
STRIPE_PUBLIC_KEY=pk_test_xxxxx
STRIPE_SECRET_KEY=sk_test_xxxxx
STRIPE_WEBHOOK_SECRET=whsec_xxxxx
```

En local, le secret webhook peut être récupéré avec la Stripe CLI en écoutant :

```bash
stripe listen --forward-to http://127.0.0.1:8081/commande/webhook/stripe
```

Messagerie
----------

En développement local, tu peux garder la boîte de capture actuelle :

```dotenv
MAILER_DSN=sendmail://default?command=/usr/bin/env%20php%20/Users/papounet/Coding/SOM_Website/SoundOfMemories_Symfony/bin/dev-sendmail.php%20-t
```

Pour envoyer de vrais emails, remplace par un SMTP ou une API compatible Symfony Mailer.

Exemples simples :

```dotenv
MAILER_DSN=smtp://utilisateur:motdepasse@smtp.example.com:587
MAILER_DSN=brevo+api://TA_CLE_API@default
MAILER_DSN=mailgun+api://TA_CLE:TON_DOMAINE@default
```

Conseil pratique
----------------

- garde `\.env` avec des valeurs vides ou d'exemple
- mets toujours les vraies clés dans [\.env.local](/Users/papounet/Coding/SOM_Website/SoundOfMemories_Symfony/.env.local)
- garde la clé Apple `.p8` dans [keys](/Users/papounet/Coding/SOM_Website/SoundOfMemories_Symfony/config/oauth/keys), déjà exclu de Git
