<?php

namespace App\Http\Controllers;

use App\Models\PageSection;

class WelcomeController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if ($user && $user->is_member) {
            return redirect()->route('dashboard');
        }

        $sections = PageSection::visible()->ordered()->get();

        return $this->view('welcome', compact('sections'));
    }
}
