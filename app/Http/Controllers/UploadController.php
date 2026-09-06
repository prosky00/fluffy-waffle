<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UploadController extends Controller
{
    public function store(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:jpg,jpeg,png,gif,ico,webp|max:4096']);

        $path = $request->file('file')->store('uploads', 'public');
        $url  = '/storage/' . $path;

        return response()->json(['url' => $url]);
    }
}
