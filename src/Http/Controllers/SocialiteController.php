<?php

namespace Bleuren\SocialiteUnify\Http\Controllers;

use App\Http\Controllers\Controller;
use Bleuren\SocialiteUnify\Contracts\SocialiteService;
use Exception;
use Illuminate\Support\Facades\Auth;

class SocialiteController extends Controller
{
    protected $socialiteService;

    public function __construct(SocialiteService $socialiteService)
    {
        $this->socialiteService = $socialiteService;
    }

    public function redirect(string $provider)
    {
        try {
            return $this->socialiteService->redirectToProvider($provider);
        } catch (Exception $e) {
            return redirect()->route('login')
                ->with('error', __('socialite.errors.unsupported_provider'));
        }
    }

    public function callback(string $provider)
    {
        try {
            $user = $this->socialiteService->handleProviderCallback($provider);
            Auth::login($user);

            return redirect()->intended('/dashboard');
        } catch (Exception $e) {
            $message = $e->getMessage();
            $providerName = __("socialite-unify::socialite.providers.{$provider}");
            $bindMessages = [
                'success' => __('socialite-unify::socialite.bind.success', ['provider' => $providerName]),
                'bound' => __('socialite-unify::socialite.bind.bound', ['provider' => $providerName]),
                'already_bound' => __('socialite-unify::socialite.bind.already_bound', ['provider' => $providerName]),
            ];

            if (in_array($message, array_values($bindMessages))) {
                $status = match ($message) {
                    $bindMessages['success'] => 'success',
                    $bindMessages['already_bound'] => 'error',
                    default => 'warning'
                };

                return redirect()->route('profile.show')->with($status, $message);
            }

            return redirect()->route('login')
                ->with('error', __('socialite-unify::socialite.errors.login_failed'));
        }
    }
}
