<?php

namespace App\Http\Controllers;

use App\Models\PageSection;

class WelcomeController extends Controller
{
    public function index()
    {
        $sections = PageSection::visible()->ordered()->get();

        return $this->view('welcome', compact('sections'));
    }
}
