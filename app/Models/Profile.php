<?php

namespace App\Models;

use App\Enums\ActivityLevel;
use App\Enums\Sex;
use App\Models\Concerns\AppartientAUtilisateur;
use Database\Factories\ProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Laraskel\Users\Models\Concerns\TracksAuthor;

/**
 * Profil de l'utilisateur (PRD §6, FR-3) : un par utilisateur, isolé (FR-5).
 *
 * @property int $id
 * @property int $user_id
 * @property Carbon $birthdate
 * @property ?Sex $sex
 * @property int $height_cm
 * @property ?string $start_weight_kg
 * @property ?Carbon $start_date
 * @property ?ActivityLevel $activity_level
 * @property ?string $medical_constraints
 * @property ?string $allergies
 * @property ?string $food_preferences
 */
class Profile extends Model
{
    use AppartientAUtilisateur;

    /** @use HasFactory<ProfileFactory> */
    use HasFactory;

    use TracksAuthor;

    protected $fillable = [
        'birthdate', 'sex', 'height_cm', 'start_weight_kg', 'start_date', 'activity_level',
        'medical_constraints', 'allergies', 'food_preferences',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birthdate' => 'date',
            'start_date' => 'date',
            'sex' => Sex::class,
            'activity_level' => ActivityLevel::class,
            'height_cm' => 'integer',
            'start_weight_kg' => 'decimal:1',
        ];
    }
}
