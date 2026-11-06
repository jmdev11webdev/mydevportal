<?php

namespace App\Http\Controllers;

use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;

class DemoLoginController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = User::where('email', DemoSeeder::EMAIL)->first();

        if ($user === null) {
            Artisan::call('db:seed', [
                '--class' => DemoSeeder::class,
                '--force' => true,
            ]);

            $user = User::where('email', DemoSeeder::EMAIL)->firstOrFail();
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
