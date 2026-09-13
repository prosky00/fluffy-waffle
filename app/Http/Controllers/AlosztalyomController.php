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
        $user   = auth()->user();
        $deptId = $this->resolveDept($request, $user);
        $this->requireManage($user, $deptId);

        Department::findOrFail($deptId)->update(['rules' => $request->input('rules')]);
        return back()->with('success', 'Szabályzat mentve.');
    }

    public function storeRank(Request $request)
    {
        $user   = auth()->user();
        $deptId = $this->resolveDept($request, $user);
        $this->requireManage($user, $deptId);

        $data = $request->validate(['name' => 'required|string|max:100', 'level' => 'required|integer']);
        DepartmentRank::create(['department_id' => $deptId, 'name' => $data['name'], 'level' => $data['level']]);
        return back()->with('success', 'Rang hozzáadva.');
    }

    public function updateRank(Request $request, $rankId)
    {
        $user   = auth()->user();
        $deptId = $this->resolveDept($request, $user);
        $this->requireManage($user, $deptId);

        $rank = DepartmentRank::where('department_id', $deptId)->findOrFail($rankId);
        $data = $request->validate(['name' => 'required|string|max:100', 'level' => 'required|integer']);
        $rank->update($data);
        return back()->with('success', 'Rang frissítve.');
    }

    public function deleteRank($rankId)
    {
        $user   = auth()->user();
        $deptId = $this->resolveDept(request(), $user);
        $this->requireManage($user, $deptId);

        DepartmentRank::where('department_id', $deptId)->findOrFail($rankId)->delete();
        return back()->with('success', 'Rang törölve.');
    }

    public function updateMember(Request $request, $userId)
    {
        $user   = auth()->user();
        $deptId = $this->resolveDept($request, $user);
        $this->requireManage($user, $deptId);

        $target = User::findOrFail($userId);
        $data   = $request->validate(['department_rank_id' => 'nullable|exists:department_ranks,id']);

        if ((int)$data['department_rank_id'] !== (int)$target->department_rank_id) {
            $newRankName = DepartmentRank::find($data['department_rank_id'])?->name;
            $target->update(['department_rank_id' => $data['department_rank_id']]);
            $this->logAudit('⭐ Előléptetés', [
                'Végrehajtotta' => $user->in_game_name ?? $user->name,
                'Tag'           => $target->in_game_name ?? $target->name,
                'Új beosztás'   => $newRankName,
            ]);
        }

        return back()->with('success', 'Tag rangja frissítve.');
    }

    private function resolveDept(Request $request, User $user): int
    {
        $deptId = $request->input('dept_id') ?: $user->department_id;
        if (!$deptId) abort(403);
        return (int)$deptId;
    }

    private function requireManage(User $user, int $deptId)
    {
        if ($user->is_admin) return;
        if (!$user->is_department_leader && !$user->is_department_deputy) abort(403);
        // Must belong to the specific department they're managing
        $isMember = (int)$user->department_id === $deptId
            || $user->departments()->where('departments.id', $deptId)->exists();
        if (!$isMember) abort(403);
    }
}
