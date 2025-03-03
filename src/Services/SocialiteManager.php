<?php

namespace Bleuren\SocialiteUnify\Services;

use App\Models\User;
use Bleuren\SocialiteUnify\Contracts\SocialiteService;
use Bleuren\SocialiteUnify\Models\SocialAccount;
use Bleuren\SocialiteUnify\Results\SocialiteResult;
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

    public function handleProviderCallback(string $provider): SocialiteResult
    {
        try {
            $socialUser = Socialite::driver($provider)->user();
            $socialAccount = $this->findSocialAccount($provider, $socialUser->getId());

            if (auth()->check()) {
                return $this->handleExistingUserCallback($provider, $socialUser, $socialAccount);
            }

            return $this->handleNewUserCallback($provider, $socialUser, $socialAccount);
        } catch (Exception $e) {
            return SocialiteResult::error('socialite-unify::socialite.errors.login_failed');
        }
    }

    protected function handleExistingUserCallback(string $provider, SocialiteUser $socialUser, ?SocialAccount $socialAccount): SocialiteResult
    {
        if ($socialAccount && $socialAccount->user_id !== auth()->id()) {
            return SocialiteResult::error('socialite-unify::socialite.bind.already_bound', [
                'provider' => $provider
            ]);
        }

        $this->bindSocialAccount(auth()->user(), $provider, $socialUser);
        return SocialiteResult::success('socialite-unify::socialite.bind.success', auth()->user(), [
            'provider' => $provider
        ]);
    }

    protected function handleNewUserCallback(string $provider, SocialiteUser $socialUser, ?SocialAccount $socialAccount): SocialiteResult
    {
        if ($socialAccount) {
            $user = User::find($socialAccount->user_id);
            return SocialiteResult::success('socialite-unify::socialite.login.success', $user);
        }

        $user = DB::transaction(function () use ($provider, $socialUser) {
            $email = $socialUser->getEmail() ?? "{$socialUser->getId()}@{$provider}.me";
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $socialUser->getName() ?? $socialUser->getNickname(),
                    'password' => Hash::make(Str::random(16)),
                    'has_password' => false,
                    'email_verified_at' => now(),
                ]
            );

            $this->bindSocialAccount($user, $provider, $socialUser);
            $this->createTeam($user);

            return $user;
        });

        return SocialiteResult::success('socialite-unify::socialite.register.success', $user);
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
