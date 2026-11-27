<?php

namespace App\Models;

use App\Models\Concerns\AppartientAUtilisateur;
use Database\Factories\GoalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Laraskel\Users\Models\Concerns\TracksAuthor;

/**
 * Objectif de l'utilisateur (FR-6 à FR-10), historisé : un changement crée une
 * nouvelle ligne, jamais de mise à jour en place (FR-9). L'objectif courant est
 * la ligne dont `effective_from` est la plus récente.
 *
 * @property int $id
 * @property int $user_id
 * @property Carbon $effective_from
 * @property string $target_weight_kg
 * @property string $target_rate_kg_per_week
 * @property int $daily_calorie_budget
 * @property ?int $protein_target_g
 * @property ?Carbon $indicative_target_date
 */
class Goal extends Model
{
    use AppartientAUtilisateur;

    /** @use HasFactory<GoalFactory> */
    use HasFactory;

    use TracksAuthor;

    protected $fillable = [
        'effective_from', 'target_weight_kg', 'target_rate_kg_per_week', 'daily_calorie_budget',
        'protein_target_g', 'indicative_target_date',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'target_weight_kg' => 'decimal:1',
            'target_rate_kg_per_week' => 'decimal:2',
            'daily_calorie_budget' => 'integer',
            'protein_target_g' => 'integer',
            'indicative_target_date' => 'date',
        ];
    }

    /**
     * Objectif en vigueur de l'utilisateur courant : le plus récent par date
     * d'effet, puis par ordre de création (deux changements le même jour).
     */
    public static function courant(): ?self
    {
        return self::query()->latest('effective_from')->latest('id')->first();
    }
}
