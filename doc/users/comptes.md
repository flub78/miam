# Comptes

Dans miam, il n'y a pas d'inscription libre : c'est l'**administrateur** qui crée les
comptes. Chaque utilisateur ne voit que ses propres données ; l'administrateur non plus
n'a accès aux données de suivi de personne.

## Première connexion

1. L'administrateur crée votre compte : vous recevez un e-mail « Réinitialisation du
   mot de passe ».
2. Suivez le lien et choisissez votre mot de passe (8 caractères minimum).
3. Connectez-vous sur `/login` avec votre adresse e-mail : vous arrivez sur votre espace
   (`/app`).

Le lien expire au bout d'une heure. S'il a expiré, utilisez **Mot de passe oublié ?**
sur l'écran de connexion, ou demandez à l'administrateur de vous en renvoyer un.

## Mot de passe oublié

Sur l'écran de connexion, **Mot de passe oublié ?** puis saisissez votre adresse : un
lien vous est envoyé. Un second envoi n'est possible qu'après un court délai.

## « Ce compte est désactivé »

L'administrateur a suspendu votre compte. Contactez-le pour qu'il le réactive : vos
données sont conservées.

## Pour l'administrateur

Les comptes se gèrent dans le panel d'administration (`/admin` → **Utilisateurs**).

| Je veux… | Comment |
|---|---|
| Créer un compte | **Créer**, renseigner nom et e-mail, choisir le rôle **user**, laisser le mot de passe vide : un lien de définition est envoyé au titulaire. |
| Suspendre un accès | Action **Désactiver** sur la ligne ; **Activer** pour le rétablir (les comptes désactivés apparaissent avec le filtre de statut). |
| Renvoyer un lien de mot de passe | Action **Envoyer un lien de mot de passe**. |
| Créer le premier administrateur | `php artisan laraskel-users:creer-admin vous@example.com --name="Vous"` (un lien de définition du mot de passe est envoyé). |

Le rôle **user** suffit pour utiliser miam ; **admin** donne en plus l'accès au panel
d'administration.
