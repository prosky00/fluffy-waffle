<?php

namespace App\Http\Controllers;

use App\Models\ChangelogEntry;

class ChangelogController extends Controller
{
    public function index()
    {
        $entries = ChangelogEntry::with('author')->latest()->get();
        return $this->view('changelog', compact('entries'));
    }
}
