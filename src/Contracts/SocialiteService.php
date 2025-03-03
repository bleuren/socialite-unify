<?php

namespace Bleuren\SocialiteUnify\Contracts;

use App\Models\User;
use Laravel\Socialite\Contracts\User as SocialiteUser;

interface SocialiteService
{
    public function getSupportedProviders(): array;

    public function redirectToProvider(string $provider): mixed;

    public function handleProviderCallback(string $provider): mixed;

    public function bindSocialAccount(User $user, string $provider, SocialiteUser $socialiteUser): bool;

    public function unbindSocialAccount(User $user, string $provider): bool;

    public function canUnbind(User $user, string $provider): bool;

    public function isBound(User $user, string $provider): bool;
}
