<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Isolation stricte par utilisateur (FR-5) pour un modèle du domaine portant une
 * colonne `user_id`.
 *
 * - **Global scope `utilisateur_courant`** : quand un utilisateur est authentifié,
 *   toute requête est restreinte à ses lignes. L'administrateur n'y échappe pas :
 *   il n'existe aucune vue inter-utilisateurs.
 * - **Affectation automatique** de `user_id` à la création, si l'attribut n'est
 *   pas déjà renseigné.
 * - **Relation** `user()` vers {@see User}.
 *
 * Hors contexte authentifié (console, seeders, jobs), le global scope ne filtre
 * pas : l'appelant est alors responsable de cibler le bon utilisateur, et aucune
 * écriture n'invente de `user_id`.
 */
trait AppartientAUtilisateur
{
    public static function bootAppartientAUtilisateur(): void
    {
        static::addGlobalScope('utilisateur_courant', function (Builder $requete): void {
            $utilisateurId = Auth::id();

            if ($utilisateurId !== null) {
                $requete->where(
                    $requete->getModel()->qualifyColumn('user_id'),
                    $utilisateurId,
                );
            }
        });

        static::creating(function (Model $modele): void {
            if ($modele->getAttribute('user_id') === null && Auth::id() !== null) {
                $modele->setAttribute('user_id', Auth::id());
            }
        });
    }

    /**
     * L'utilisateur propriétaire de la ligne.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
