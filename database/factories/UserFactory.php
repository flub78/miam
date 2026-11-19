<?php

namespace Database\Factories;

use App\Models\User;
use Laraskel\Users\Database\Factories\UserFactory as BaseUserFactory;

/**
 * Factory de `App\Models\User`.
 *
 * Réutilise la définition et les états (`unverified()`, `inactive()`, `admin()`)
 * de la factory de la brique `flub78/laraskel-users` ; seule la cible change.
 *
 * @extends BaseUserFactory<User>
 */
class UserFactory extends BaseUserFactory
{
    /**
     * @var class-string<User>
     */
    protected $model = User::class;
}
