<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;

class AlosztalyAdatbazisController extends Controller
{
    public function index()
    {
        $departments = Department::withCount('members')->with('ranks')->orderBy('name')->get();
        return $this->view('alosztaly-adatbazis', compact('departments'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100|unique:departments',
            'short_name'  => 'required|string|max:20',
            'max_members' => 'required|integer|min:0',
        ]);
        Department::create($data);
        return back()->with('success', 'Alosztály létrehozva.');
    }

    public function update(Request $request, $id)
    {
        $dept = Department::findOrFail($id);
        $data = $request->validate([
            'name'        => 'required|string|max:100|unique:departments,name,' . $id,
            'short_name'  => 'required|string|max:20',
            'max_members' => 'required|integer|min:0',
        ]);
        $dept->update($data);
        return back()->with('success', 'Alosztály frissítve.');
    }

    public function destroy($id)
    {
        Department::findOrFail($id)->delete();
        return back()->with('success', 'Alosztály törölve.');
    }
}
