<?php

use Laravel\Fortify\Features;

return [

    /*
    |--------------------------------------------------------------------------
    | Redirection après connexion
    |--------------------------------------------------------------------------
    |
    | Chemin vers lequel Fortify redirige après une connexion ou une
    | réinitialisation de mot de passe réussie. Appliqué à `fortify.home` par le
    | fournisseur de services de la brique s'il n'a pas été fixé par ailleurs.
    |
    */

    'home' => env('LARASKEL_USERS_HOME', '/app'),

    /*
    |--------------------------------------------------------------------------
    | Écrans d'authentification et de profil
    |--------------------------------------------------------------------------
    |
    | Les vues `laraskel-users::auth.*` et `laraskel-users::profile.show` sont
    | autoportantes (feuille de style intégrée). Une application avec sa propre
    | charte publie les vues (`php artisan vendor:publish
    | --tag=laraskel-users-views`) et les restyle.
    |
    | - theme      : valeur de l'attribut `data-theme` posé sur `<html>` (thème
    |                daisyUI par exemple). `null` : aucun attribut.
    | - brand_logo : chemin d'asset (`asset()`) affiché en en-tête des écrans.
    |                `null` : nom de l'application en texte.
    | - favicon    : chemin d'asset ; `null` : `favicon.ico` de l'application.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Fonctionnalités Fortify
    |--------------------------------------------------------------------------
    |
    | Appliquées à `fortify.features` par la brique **uniquement si
    | l'application n'a pas publié `config/fortify.php`** (sinon le choix de
    | l'application prime). La brique fixe alors aussi `fortify.limiters.login`
    | (5 tentatives par minute) et `fortify.lowercase_usernames`.
    |
    | Fonctionnalités prises en charge par la brique (vues fournies) :
    | - Features::registration()             : inscription libre (`/register`).
    |                                          À retirer si seul l'administrateur
    |                                          crée les comptes.
    | - Features::resetPasswords()           : mot de passe oublié / lien de
    |                                          définition envoyé par l'administrateur.
    | - Features::emailVerification()        : vérification de l'adresse e-mail.
    | - Features::updateProfileInformation() : modification du profil.
    | - Features::updatePasswords()          : changement de mot de passe.
    |
    | Non prises en charge (ni vues ni migrations dans la brique) :
    | Features::twoFactorAuthentication(), Features::passkeys().
    |
    */

    'fortify' => [
        'features' => [
            // miam : pas d'inscription libre, les comptes sont créés par
            // l'administrateur (PRD FR-1).
            Features::resetPasswords(),
            Features::emailVerification(),
            Features::updateProfileInformation(),
            Features::updatePasswords(),
        ],
    ],

    'theme' => env('LARASKEL_USERS_THEME'),
    'brand_logo' => env('LARASKEL_USERS_BRAND_LOGO'),
    'favicon' => env('LARASKEL_USERS_FAVICON'),

    /*
    |--------------------------------------------------------------------------
    | Panel d'administration Filament
    |--------------------------------------------------------------------------
    |
    | Le package fournit un panel d'administration monté par défaut sur `/admin`
    | (gestion des comptes et des rôles).
    |
    | - default : ce panel est-il le panel Filament par défaut de l'application.
    |             Mettre `false` dans une application qui déclare son propre
    |             panel utilisateur métier comme panel par défaut.
    | - path       : préfixe d'URL du panel.
    | - brand_name : nom affiché dans l'en-tête du panel.
    | - brand_logo / dark_brand_logo / favicon : chemins d'assets (`asset()`),
    |                laissés à `null` pour utiliser le rendu texte par défaut.
    | - primary_color : couleur primaire (hexadécimal).
    |
    */

    'admin_panel' => [
        'default' => env('LARASKEL_USERS_ADMIN_PANEL_DEFAULT', false),
        'path' => env('LARASKEL_USERS_ADMIN_PANEL_PATH', 'admin'),
        'brand_name' => env('LARASKEL_USERS_ADMIN_BRAND_NAME', 'miam — administration'),
        'brand_logo' => null,
        'dark_brand_logo' => null,
        'favicon' => null,
        'primary_color' => '#2E7D32',
    ],

];
