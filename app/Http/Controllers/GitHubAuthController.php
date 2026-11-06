<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

class GitHubAuthController extends Controller
{
    /**
     * Redirect the developer to GitHub's OAuth consent screen.
     */
    public function redirect(): RedirectResponse|SymfonyRedirectResponse
    {
        abort_unless($this->configured(), 404);

        return Socialite::driver('github')->redirect();
    }

    /**
     * Handle the callback from GitHub and sign the developer in.
     */
    public function callback(Request $request): RedirectResponse
    {
        abort_unless($this->configured(), 404);

        try {
            $githubUser = Socialite::driver('github')->user();
        } catch (Throwable) {
            return redirect()->route('login')->withErrors([
                'github' => 'We could not authenticate with GitHub. Please try again.',
            ]);
        }

        $email = $githubUser->getEmail();

        if ($email === null) {
            return redirect()->route('login')->withErrors([
                'github' => 'Your GitHub account does not expose an email address. Add one to GitHub and try again.',
            ]);
        }

        $user = User::query()
            ->where('github_id', $githubUser->getId())
            ->orWhere('email', $email)
            ->first();

        if ($user === null) {
            $user = User::query()->create([
                'name' => $githubUser->getName() ?? $githubUser->getNickname() ?? 'GitHub developer',
                'email' => $email,
                'password' => Str::random(40),
                'github_id' => $githubUser->getId(),
                'github_username' => $githubUser->getNickname(),
                'github_avatar' => $githubUser->getAvatar(),
            ]);
        } else {
            $user->forceFill([
                'github_id' => $githubUser->getId(),
                'github_username' => $githubUser->getNickname(),
                'github_avatar' => $githubUser->getAvatar(),
            ])->save();
        }

        if ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        if ($user->two_factor_secret !== null && $user->two_factor_confirmed_at !== null) {
            $request->session()->put([
                'login.id' => $user->getKey(),
                'login.remember' => true,
            ]);

            return redirect()->route('two-factor.login');
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Determine whether GitHub OAuth has been configured.
     */
    private function configured(): bool
    {
        return filled(config('services.github.client_id'))
            && filled(config('services.github.client_secret'));
    }
}
