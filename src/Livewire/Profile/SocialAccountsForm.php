<?php

namespace Bleuren\SocialiteUnify\Livewire\Profile;

use Bleuren\SocialiteUnify\Contracts\SocialiteService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SocialAccountsForm extends Component
{
    public $confirmingUnbind = false;

    public $selectedProvider = null;

    protected $socialiteService;

    public function boot(SocialiteService $socialiteService)
    {
        $this->socialiteService = $socialiteService;
    }

    public function getListeners()
    {
        return [
            'refresh-social-accounts' => '$refresh',
        ];
    }

    public function confirmUnbindSocialAccount($provider)
    {
        $this->selectedProvider = $provider;
        $this->confirmingUnbind = true;
    }

    public function unbindSocialAccount()
    {
        try {
            $user = Auth::user();
            $provider = $this->selectedProvider;

            if (! $this->socialiteService->canUnbind($user, $provider)) {
                session()->flash('error', __('socialite-unify::socialite.errors.last_account'));
                $this->confirmingUnbind = false;

                return;
            }

            $this->socialiteService->unbindSocialAccount($user, $provider);
            session()->flash('success', __('socialite-unify::socialite.bind.unbound_success', [
                'provider' => __("socialite-unify::socialite.providers.{$provider}"),
            ]));

            $this->confirmingUnbind = false;
            $this->selectedProvider = null;
            $this->dispatch('social-account-unbound');
        } catch (Exception $e) {
            session()->flash('error', __('socialite-unify::socialite.bind.errors.unbind_failed'));
        }
    }

    /**
     * 檢查指定的社交帳號是否已綁定
     */
    public function isBound(string $provider): bool
    {
        return $this->socialiteService->isBound(auth()->user(), $provider);
    }

    public function render()
    {
        return view('socialite-unify::social-accounts-form', [
            'providers' => $this->socialiteService->getSupportedProviders(),
        ]);
    }
}
