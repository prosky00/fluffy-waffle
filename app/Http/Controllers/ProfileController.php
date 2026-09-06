<?php

namespace App\Http\Controllers;

class ProfileController extends Controller
{
    public function show()
    {
        $user = auth()->user()->load(['rank', 'department', 'departmentRank']);
        return $this->view('profile', compact('user'));
    }
}
