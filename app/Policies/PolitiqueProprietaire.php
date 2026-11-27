<?php

namespace App\Policies;

use App\Models\Concerns\AppartientAUtilisateur;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Politique de base des modèles du domaine : un utilisateur n'agit que sur ses
 * propres lignes (FR-5).
 *
 * Chaque modèle du domaine a une policy concrète qui étend celle-ci
 * (`App\Policies\ProfilePolicy` pour `App\Models\Profile`, etc.) ; l'auto-découverte
 * Laravel les associe au modèle par convention de nommage.
 *
 * Le global scope du trait {@see AppartientAUtilisateur}
 * empêche déjà de charger la ligne d'un autre utilisateur. Cette policy double la
 * protection pour les vérifications explicites (actions Filament, `authorize()`).
 */
abstract class PolitiqueProprietaire
{
    public function viewAny(User $utilisateur): bool
    {
        return true;
    }

    public function view(User $utilisateur, Model $modele): bool
    {
        return $this->appartientA($utilisateur, $modele);
    }

    public function create(User $utilisateur): bool
    {
        return true;
    }

    public function update(User $utilisateur, Model $modele): bool
    {
        return $this->appartientA($utilisateur, $modele);
    }

    public function delete(User $utilisateur, Model $modele): bool
    {
        return $this->appartientA($utilisateur, $modele);
    }

    /**
     * Vrai si la ligne appartient à l'utilisateur.
     */
    protected function appartientA(User $utilisateur, Model $modele): bool
    {
        return $modele->getAttribute('user_id') !== null
            && (int) $modele->getAttribute('user_id') === (int) $utilisateur->getKey();
    }
}
