<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;

class LekerdezoController extends Controller
{
    public function index(Request $request)
    {
        $user  = auth()->user();
        $query = $request->get('q');

        $reports = collect();
        if ($query) {
            $q = Report::with(['author', 'connectedUsers'])
                ->where('title', 'like', "%{$query}%");

            if (!$user->is_admin) {
                $q->whereIn('status', ['SUBMITTED', 'APPROVED']);
            }

            $reports = $q->latest()->paginate(20)->withQueryString();
        }

        return $this->view('lekerdezo', compact('reports', 'query'));
    }
}
