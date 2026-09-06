<?php

namespace App\Http\Controllers;

use App\Models\Announcement;

class AnnouncementController extends Controller
{
    public function show($id)
    {
        $announcement = Announcement::with('author')->findOrFail($id);
        return $this->view('announcement-show', compact('announcement'));
    }
}
