<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SettingsController extends Controller
{
    public function show()
    {
        return $this->view(auth()->user()->is_member ? 'settings-page' : 'guest-settings');
    }

    public function update(Request $request)
    {
        $request->validate(['in_game_name' => 'nullable|string|max:100']);
        auth()->user()->update(['in_game_name' => $request->in_game_name]);
        return back()->with('success', 'Beállítások mentve.');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password'      => 'required|string',
            'new_password'          => 'required|string|min:6|confirmed',
        ]);

        $user = auth()->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'A jelenlegi jelszó helytelen.']);
        }

        $user->update(['password' => $request->new_password]); // cast 'hashed' auto-hashes

        return back()->with('success', 'Jelszó sikeresen megváltoztatva.');
    }
}
