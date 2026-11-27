<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laraskel\Users\Models\User as BaseUser;
use Spatie\Permission\Models\Role;

/**
 * Utilisateur de miam.
 *
 * Étend le modèle de la brique `flub78/laraskel-users` par composition :
 * attributs, rôles, contrat Filament et cycle de vie des comptes viennent du
 * parent. miam y ajoute ses relations de domaine, le rôle `user` par défaut et
 * l'accès au panel utilisateur.
 */
class User extends BaseUser
{
    /**
     * Rôle attribué à tout compte créé sans rôle explicite.
     */
    public const ROLE_PAR_DEFAUT = 'user';

    /**
     * Identifiant du panel Filament des utilisateurs (`App\Providers\Filament\AppPanelProvider`).
     */
    public const PANEL_UTILISATEUR = 'app';

    protected static function booted(): void
    {
        static::created(function (User $utilisateur): void {
            if ($utilisateur->roles()->doesntExist()) {
                $utilisateur->assignRole(Role::findOrCreate(self::ROLE_PAR_DEFAUT, (string) config('auth.defaults.guard', 'web')));
            }
        });
    }

    /**
     * Panel utilisateur : tout compte actif. Panel d'administration : règle de la
     * brique (administrateur actif).
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === self::PANEL_UTILISATEUR) {
            return (bool) $this->is_active;
        }

        return parent::canAccessPanel($panel);
    }

    /**
     * @return HasOne<Profile, $this>
     */
    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    /**
     * @return HasMany<AcquiredHabitChange, $this>
     */
    public function acquiredHabitChanges(): HasMany
    {
        return $this->hasMany(AcquiredHabitChange::class);
    }

    /**
     * @return HasMany<Goal, $this>
     */
    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class);
    }

    /**
     * @return HasOne<TrackingSettings, $this>
     */
    public function trackingSettings(): HasOne
    {
        return $this->hasOne(TrackingSettings::class);
    }

    /**
     * @return HasMany<WeighIn, $this>
     */
    public function weighIns(): HasMany
    {
        return $this->hasMany(WeighIn::class);
    }

    /**
     * @return HasMany<SuspensionPeriod, $this>
     */
    public function suspensionPeriods(): HasMany
    {
        return $this->hasMany(SuspensionPeriod::class);
    }

    protected static function newFactory(): Factory
    {
        return UserFactory::new();
    }
}
