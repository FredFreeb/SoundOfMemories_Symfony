Configuration mail locale
=========================

Mailcow
-------

Mailcow est une suite mail open source auto-hébergée. C'est une bonne option si tu veux garder la main sur ton infrastructure, mais ce n'est pas un simple provider SaaS : il faut un vrai serveur Linux dédié, Docker, un nom de domaine mail et une configuration DNS propre.

Pré-requis officiels côté Mailcow :

- une machine Linux dédiée
- Docker `>= 24`
- Docker Compose `>= 2`
- des ports mail ouverts
- un DNS correct

Pour ce projet Symfony, Mailcow ne se branche pas via une API propriétaire. Une fois ton serveur Mailcow prêt, le site utilise simplement SMTP via `MAILER_DSN`.

Exemple type pour `\.env.local` :

```dotenv
MAILER_DSN=smtp://mailer%40ton-domaine.tld:TON_MOT_DE_PASSE@mail.ton-domaine.tld:587?require_tls=true
```

Notes :

- `%40` correspond à `@` dans l'identifiant SMTP
- `587` convient au plus souvent avec STARTTLS
- `require_tls=true` force l'usage de TLS

Exemple plus simple :

```dotenv
MAILER_DSN=smtp://user:password@mail.ton-domaine.tld:587
```

Ce qu'il te faudra depuis Mailcow :

- l'hôte SMTP, par exemple `mail.ton-domaine.tld`
- l'identifiant SMTP
- le mot de passe SMTP
- un domaine mail authentifié avec SPF, DKIM et DMARC

Ce que je peux préparer pour toi
--------------------------------

Depuis ce projet, je peux :

- préparer le `MAILER_DSN`
- tester l'envoi Symfony
- garder la boîte mail locale de dev pour les tests
- basculer proprement vers Mailcow quand tu as l'hôte SMTP

Ce que je ne peux pas installer entièrement depuis ce repo
----------------------------------------------------------

L'installation complète de Mailcow se fait au niveau serveur :

- Docker
- DNS
- certificats
- firewall
- ports entrants / sortants

Ce n'est donc pas quelque chose que je peux finaliser uniquement dans le code du site sans ton serveur cible.
