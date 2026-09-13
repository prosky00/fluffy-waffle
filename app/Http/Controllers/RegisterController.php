<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class RegisterController extends Controller
{
    public function create()
    {
        if (auth()->check()) {
            return redirect()->route('welcome');
        }

        return $this->view('register');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'username'     => 'required|string|max:50|alpha_dash|unique:users,username',
            'password'     => 'required|string|min:6',
            'in_game_name' => 'required|string|max:100',
        ]);

        $user = User::create([
            'username'     => $data['username'],
            'password'     => $data['password'],
            'name'         => $data['in_game_name'],
            'in_game_name' => $data['in_game_name'],
            'is_member'    => false,
        ]);

        auth()->login($user);
        $request->session()->regenerate();

        return redirect()->route('applications.index')->with('success', 'Fiókod elkészült! Most már be tudsz jelentkezni a frakcióba.');
    }
}
