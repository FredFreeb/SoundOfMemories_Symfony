# Securite et comptes

## Ce qui est en place

- Le back-office n'est plus expose sur `/admin` mais sur `/la-porte-secrete`.
- Les comptes clients ont maintenant un espace personnel pour retrouver leurs commandes.
- Les commandes peuvent etre rattachees a un compte client pour consolider l'historique.

## Ce que je prevois pour la suite

- Passkeys / FIDO2:
  j'aurai besoin d'une integration WebAuthn cote Symfony et de tests sur Safari, Chrome et iPhone.
- Connexion Apple, Google, Proton:
  j'aurai besoin d'un vrai schema OAuth/OIDC avec gestion des retours de providers.
- Messagerie client <-> admin chiffree de bout en bout:
  j'aurai besoin d'un chiffrement realise cote navigateur avec gestion de cles par utilisateur.

## Point important

Je ne considere pas qu'une simple messagerie en base Symfony soit du vrai chiffrement de bout en bout.
Si je mets en place ce module plus tard, je devrai gerer:

- une cle publique par client
- une cle publique par administrateur ou par espace support
- un chiffrement avant envoi depuis le navigateur
- une gestion de rotation et de recuperation des cles

Sans cela, ce serait une messagerie authentifiee et securisee cote serveur, mais pas du E2E au sens strict.
