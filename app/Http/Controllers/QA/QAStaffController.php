<?php

namespace App\Http\Controllers\QA;

use App\Http\Controllers\Controller;
use App\Models\StaffMember;
use App\Models\StaffRole;
use Illuminate\Http\Request;

class QAStaffController extends Controller
{
    public function index(Request $request)
    {
        $roles = StaffRole::all();
        $selectedRole = $request->input('role', $roles->first()->role_key ?? null);
        
        $staffMembers = StaffMember::with('role')
            ->when($selectedRole, function ($query, $roleKey) {
                $query->whereHas('role', function ($q) use ($roleKey) {
                    $q->where('role_key', $roleKey);
                });
            })
            ->orderBy('name_ar')
            ->get();

        return view('qa.staff.index', compact('roles', 'staffMembers', 'selectedRole'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'staff_role_id' => 'required|exists:staff_roles,id',
            'name_ar' => 'required|string|max:255',
        ]);

        StaffMember::create([
            'staff_role_id' => $request->staff_role_id,
            'name_ar' => $request->name_ar,
            'is_active' => true,
        ]);

        return redirect()->back()->with('success', 'Staff member added successfully.');
    }

    public function update(Request $request, $id)
    {
        $staff = StaffMember::findOrFail($id);
        
        $request->validate([
            'name_ar' => 'required|string|max:255',
        ]);

        $staff->update([
            'name_ar' => $request->name_ar,
        ]);

        return redirect()->back()->with('success', 'Staff member updated successfully.');
    }

    public function toggle($id)
    {
        $staff = StaffMember::findOrFail($id);
        $staff->update(['is_active' => !$staff->is_active]);

        return redirect()->back()->with('success', 'Staff member status updated.');
    }
}
