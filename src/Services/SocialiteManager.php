<?php

namespace Bleuren\SocialiteUnify\Services;

use App\Models\User;
use Bleuren\SocialiteUnify\Contracts\SocialiteService;
use Bleuren\SocialiteUnify\Models\SocialAccount;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;

class SocialiteManager implements SocialiteService
{
    protected array $providers = ['line'];

    public function getSupportedProviders(): array
    {
        return $this->providers;
    }

    public function redirectToProvider(string $provider): mixed
    {
        if (! in_array($provider, $this->providers)) {
            throw new Exception(__('socialite-unify::socialite.errors.unsupported_provider'));
        }

        return Socialite::driver($provider)->redirect();
    }

    public function handleProviderCallback(string $provider): mixed
    {
        $socialUser = Socialite::driver($provider)->user();
        $socialAccount = $this->findSocialAccount($provider, $socialUser->getId());

        if (auth()->check()) {
            if ($socialAccount && $socialAccount->user_id !== auth()->id()) {
                throw new Exception(__('socialite-unify::socialite.bind.already_bound', [
                    'provider' => __("socialite-unify::socialite.providers.{$provider}"),
                ]));
            }

            $this->bindSocialAccount(auth()->user(), $provider, $socialUser);
            throw new Exception(__('socialite-unify::socialite.bind.success', [
                'provider' => __("socialite-unify::socialite.providers.{$provider}"),
            ]));
        }

        if ($socialAccount) {
            return User::find($socialAccount->user_id);
        }

        return DB::transaction(function () use ($provider, $socialUser) {
            $email = $socialUser->getEmail() ?? "{$socialUser->getId()}@{$provider}.me";
            $user = User::where('email', $email)->first();

            if (! $user) {
                $user = User::create([
                    'name' => $socialUser->getName() ?? $socialUser->getNickname(),
                    'email' => $email,
                    'password' => Hash::make(Str::random(16)),
                    'has_password' => false,
                    'email_verified_at' => now(),
                ]);

                $this->createTeam($user);
            }

            $this->bindSocialAccount($user, $provider, $socialUser);

            return $user;
        });
    }

    public function bindSocialAccount(User $user, string $provider, SocialiteUser $socialiteUser): bool
    {
        $existingAccount = $this->findSocialAccount($provider, $socialiteUser->getId());

        if ($existingAccount && $existingAccount->user_id !== $user->id) {
            throw new Exception(__('socialite-unify::socialite.bind.already_bound', [
                'provider' => __("socialite-unify::socialite.providers.{$provider}"),
            ]));
        }

        if (! $existingAccount) {
            $user->socialAccounts()->create([
                'provider_name' => $provider,
                'provider_id' => $socialiteUser->getId(),
            ]);
        }

        return true;
    }

    public function unbindSocialAccount(User $user, string $provider): bool
    {
        if (! $this->canUnbind($user, $provider)) {
            throw new Exception(__('socialite-unify::socialite.errors.last_account'));
        }

        return (bool) $user->socialAccounts()
            ->where('provider_name', $provider)
            ->delete();
    }

    protected function findSocialAccount(string $provider, string $providerId)
    {
        return SocialAccount::where('provider_name', $provider)
            ->where('provider_id', $providerId)
            ->first();
    }

    public function canUnbind(User $user, string $provider): bool
    {
        $boundAccounts = $user->socialAccounts()->count();

        return $boundAccounts > 1 || $user->has_password;
    }

    protected function createTeam(User $user): void
    {
        $user->ownedTeams()->save(\App\Models\Team::forceCreate([
            'user_id' => $user->id,
            'name' => explode(' ', $user->name, 2)[0]."'s Team",
            'personal_team' => true,
        ]));
    }

    public function isBound(User $user, string $provider): bool
    {
        return $user->socialAccounts()
            ->where('provider_name', $provider)
            ->exists();
    }
}
