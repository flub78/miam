<?php

namespace App\Models;

use App\Models\Concerns\AppartientAUtilisateur;
use Database\Factories\TrackingSettingsFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Laraskel\Users\Models\Concerns\TracksAuthor;

/**
 * Réglages du suivi, un par utilisateur : intervalle entre deux bilans et date
 * du prochain bilan (FR-42). Table créée au Lot 2, exploitée au Lot 5.
 *
 * @property int $id
 * @property int $user_id
 * @property int $review_interval_days
 * @property ?Carbon $next_review_on
 */
class TrackingSettings extends Model
{
    use AppartientAUtilisateur;

    /** @use HasFactory<TrackingSettingsFactory> */
    use HasFactory;

    use TracksAuthor;

    protected $fillable = ['review_interval_days', 'next_review_on'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'review_interval_days' => 'integer',
            'next_review_on' => 'date',
        ];
    }
}
