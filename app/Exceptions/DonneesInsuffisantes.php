<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Un calcul ne peut pas aboutir faute de données saisies par l'utilisateur
 * (profil incomplet, aucune pesée…). Le message dit quoi renseigner et où ;
 * il est affiché tel quel à l'utilisateur.
 */
class DonneesInsuffisantes extends RuntimeException {}
