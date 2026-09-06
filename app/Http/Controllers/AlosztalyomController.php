<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\DepartmentRank;
use App\Models\User;
use Illuminate\Http\Request;

class AlosztalyomController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Determine which dept to show: ?dept= param, then primary, then first pivot dept
        $deptId = request('dept')
            ?: $user->department_id
            ?: $user->departments()->first()?->id;

        if (!$deptId) {
            return $this->view('alosztalyom', ['department' => null, 'canManage' => false]);
        }

        // Must belong to the dept (or be admin)
        $isMember = (int)$user->department_id === (int)$deptId
            || $user->departments()->where('departments.id', $deptId)->exists()
            || $user->is_admin;

        if (!$isMember) abort(403);

        $department = Department::with(['members.rank', 'members.departmentRank', 'ranks'])->findOrFail($deptId);
        $canManage  = $user->is_admin || $user->is_department_leader || $user->is_department_deputy;

        return $this->view('alosztalyom', compact('department', 'canManage'));
    }

    public function updateRules(Request $request)
    {
        $user = auth()->user();
        $this->requireManage($user);

        $dept = Department::findOrFail($user->department_id);
        $dept->update(['rules' => $request->input('rules')]);
        return back()->with('success', 'Szabályzat mentve.');
    }

    public function storeRank(Request $request)
    {
        $user = auth()->user();
        $this->requireManage($user);

        $data = $request->validate(['name' => 'required|string|max:100', 'level' => 'required|integer']);
        DepartmentRank::create(['department_id' => $user->department_id, 'name' => $data['name'], 'level' => $data['level']]);
        return back()->with('success', 'Rang hozzáadva.');
    }

    public function updateRank(Request $request, $rankId)
    {
        $user = auth()->user();
        $this->requireManage($user);

        $rank = DepartmentRank::where('department_id', $user->department_id)->findOrFail($rankId);
        $data = $request->validate(['name' => 'required|string|max:100', 'level' => 'required|integer']);
        $rank->update($data);
        return back()->with('success', 'Rang frissítve.');
    }

    public function deleteRank($rankId)
    {
        $user = auth()->user();
        $this->requireManage($user);

        DepartmentRank::where('department_id', $user->department_id)->findOrFail($rankId)->delete();
        return back()->with('success', 'Rang törölve.');
    }

    public function updateMember(Request $request, $userId)
    {
        $user = auth()->user();
        $this->requireManage($user);

        $target = User::where('department_id', $user->department_id)->findOrFail($userId);
        $data   = $request->validate(['department_rank_id' => 'nullable|exists:department_ranks,id']);
        $target->update(['department_rank_id' => $data['department_rank_id']]);
        return back()->with('success', 'Tag rangja frissítve.');
    }

    private function requireManage(User $user)
    {
        if (!$user->is_admin && !$user->is_department_leader && !$user->is_department_deputy) {
            abort(403);
        }
        if (!$user->department_id) {
            abort(403);
        }
    }
}
