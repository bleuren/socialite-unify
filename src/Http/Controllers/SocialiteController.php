<?php

namespace Bleuren\SocialiteUnify\Http\Controllers;

use App\Http\Controllers\Controller;
use Bleuren\SocialiteUnify\Contracts\SocialiteService;
use Exception;
use Illuminate\Support\Facades\Auth;

class SocialiteController extends Controller
{
    public function __construct(
        protected SocialiteService $socialiteService
    ) {}

    public function redirect(string $provider)
    {
        try {
            return $this->socialiteService->redirectToProvider($provider);
        } catch (Exception $e) {
            return redirect()->route('login')
                ->with('error', __('socialite-unify::socialite.errors.unsupported_provider'));
        }
    }

    public function callback(string $provider)
    {
        $result = $this->socialiteService->handleProviderCallback($provider);

        if (auth()->check()) {
            return redirect()->route('profile.show')
                ->with($result->status, __($result->message, $result->context));
        }

        if ($result->status === 'success' && $result->user) {
            Auth::login($result->user);

            return redirect()->intended('/dashboard');
        }

        return redirect()->route('login')
            ->with($result->status, __($result->message, $result->context));
    }
}
