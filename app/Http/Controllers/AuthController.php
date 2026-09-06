<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function login()
    {
        if (auth()->check()) return redirect('/');
        return view('login');
    }

    public function authenticate(Request $request)
    {
        $data = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $data['username'])->first();

        if (!$user || !$user->password || !\Illuminate\Support\Facades\Hash::check($data['password'], $user->password)) {
            return back()->withErrors(['username' => 'Hibás felhasználónév vagy jelszó.'])->withInput(['username' => $data['username']]);
        }

        if ($user->is_suspended) {
            return back()->withErrors(['username' => 'A fiókod felfüggesztve. Lépj kapcsolatba egy adminisztrátorral.'])->withInput(['username' => $data['username']]);
        }

        auth()->login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended('/');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }

    // Discord linking — only for already-authenticated users
    public function discordLink()
    {
        if (!auth()->check()) return redirect('/login');
        session(['discord_action' => 'link']);
        return Socialite::driver('discord')->redirect();
    }

    public function callback(Request $request)
    {
        try {
            $discordUser = Socialite::driver('discord')->user();
        } catch (\Exception $e) {
            return redirect('/login')->withErrors(['username' => 'Discord hiba. Próbáld újra.']);
        }

        $action = session('discord_action');
        session()->forget('discord_action');

        if ($action === 'link' && auth()->check()) {
            $taken = User::where('discord_id', $discordUser->getId())
                ->where('id', '!=', auth()->id())
                ->exists();

            if ($taken) {
                return redirect('/profile')->withErrors(['discord' => 'Ez a Discord fiók már egy másik fiókhoz van kötve.']);
            }

            auth()->user()->update([
                'discord_id' => $discordUser->getId(),
                'avatar'     => $discordUser->getAvatar() ?? auth()->user()->avatar,
            ]);

            return redirect('/profile')->with('success', 'Discord fiók sikeresen csatolva. Mostantól értesítéseket kapsz Discordon is.');
        }

        // Direct Discord login is disabled — username/password only
        return redirect('/login')->withErrors(['username' => 'Kérjük, felhasználónév és jelszóval jelentkezz be.']);
    }

    public function discordUnlink()
    {
        auth()->user()->update(['discord_id' => null, 'avatar' => null]);
        return redirect('/profile')->with('success', 'Discord csatolás eltávolítva.');
    }
}
