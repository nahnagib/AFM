@extends('layouts.app')

@section('title', 'Staff Management')

@section('content')
<div class="container mx-auto px-4 py-8 bg-[#F6F8FB]">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-[#0F172A]">Staff Management</h1>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Sidebar: Roles -->
        <div class="bg-[#F1F5F9] rounded-xl border border-[#E5E7EB] p-5 shadow-[0_1px_2px_rgba(0,0,0,0.04)]">
            <h3 class="text-base font-semibold mb-4 text-[#0F172A]">Roles</h3>
            <ul class="space-y-2">
                @foreach($roles as $role)
                    <li>
                        <a href="{{ route('qa.staff.index', ['role' => $role->role_key]) }}" 
                           class="block px-4 py-2 rounded-lg transition {{ $selectedRole === $role->role_key ? 'bg-[#DBEAFE] text-[#2563EB] font-semibold' : 'text-[#475569] hover:bg-white' }}">
                            {{ $role->label_ar }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>

        <!-- Main Content: Staff List -->
        <div class="lg:col-span-3">
            <div class="bg-white rounded-xl border border-[#E5E7EB] p-6 shadow-[0_1px_2px_rgba(0,0,0,0.04)]">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold text-[#0F172A]">
                        @if($selectedRole)
                            {{ $roles->where('role_key', $selectedRole)->first()->label_ar }}
                        @else
                            Select a Role
                        @endif
                    </h2>
                    @if($selectedRole)
                        <button onclick="document.getElementById('addStaffModal').classList.remove('hidden')" 
                                class="bg-[#2563EB] hover:bg-[#1D4ED8] text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                            Add Staff Member
                        </button>
                    @endif
                </div>

                @if($staffMembers->isEmpty())
                    <div class="flex flex-col items-center justify-center py-16 text-center text-sm text-[#94A3B8]">
                        No staff members found for this role.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-[#E5E7EB]">
                                    <th class="py-3 px-4 text-xs font-semibold tracking-wide text-[#475569] uppercase">Name (Arabic)</th>
                                    <th class="py-3 px-4 text-xs font-semibold tracking-wide text-[#475569] uppercase">Status</th>
                                    <th class="py-3 px-4 text-xs font-semibold tracking-wide text-[#475569] uppercase text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($staffMembers as $staff)
                                    <tr class="border-b border-[#E5E7EB] hover:bg-[#F8FAFC] transition">
                                        <td class="py-3 px-4 text-sm text-[#0F172A]">{{ $staff->name_ar }}</td>
                                        <td class="py-3 px-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $staff->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                                {{ $staff->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-right space-x-2">
                                            <button onclick="editStaff({{ $staff->id }}, '{{ $staff->name_ar }}')" 
                                                    class="text-sm font-medium text-[#2563EB] hover:text-[#1D4ED8]">
                                                Edit
                                            </button>
                                            <form action="{{ route('qa.staff.toggle', $staff->id) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="text-sm font-medium text-[#475569] hover:text-[#0F172A]">
                                                    {{ $staff->is_active ? 'Deactivate' : 'Activate' }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Add Staff Modal -->
<div id="addStaffModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-xl border border-[#E5E7EB] shadow-[0_1px_2px_rgba(0,0,0,0.04)] w-full max-w-md p-6">
        <h3 class="text-lg font-bold mb-4 text-[#0F172A]">Add New Staff Member</h3>
        <form action="{{ route('qa.staff.store') }}" method="POST">
            @csrf
            <input type="hidden" name="staff_role_id" value="{{ $roles->where('role_key', $selectedRole)->first()->id ?? '' }}">
            <div class="mb-4">
                <label class="block text-sm font-medium text-[#475569] mb-1">Name (Arabic)</label>
                <input type="text" name="name_ar" required class="w-full rounded-lg border border-[#E5E7EB] bg-white text-[#0F172A] shadow-sm focus:border-[#2563EB] focus:ring-[#2563EB] focus:ring-1">
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="document.getElementById('addStaffModal').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-[#475569] hover:bg-[#F1F5F9] rounded-lg">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-[#2563EB] hover:bg-[#1D4ED8] rounded-lg">
                    Add Staff
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Staff Modal -->
<div id="editStaffModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-xl border border-[#E5E7EB] shadow-[0_1px_2px_rgba(0,0,0,0.04)] w-full max-w-md p-6">
        <h3 class="text-lg font-bold mb-4 text-[#0F172A]">Edit Staff Member</h3>
        <form id="editStaffForm" method="POST">
            @csrf
            @method('PUT')
            <div class="mb-4">
                <label class="block text-sm font-medium text-[#475569] mb-1">Name (Arabic)</label>
                <input type="text" id="editNameAr" name="name_ar" required class="w-full rounded-lg border border-[#E5E7EB] bg-white text-[#0F172A] shadow-sm focus:border-[#2563EB] focus:ring-[#2563EB] focus:ring-1">
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="document.getElementById('editStaffModal').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-[#475569] hover:bg-[#F1F5F9] rounded-lg">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-[#2563EB] hover:bg-[#1D4ED8] rounded-lg">
                    Update Staff
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function editStaff(id, name) {
        const modal = document.getElementById('editStaffModal');
        const form = document.getElementById('editStaffForm');
        const nameInput = document.getElementById('editNameAr');
        
        form.action = `/qa/staff/${id}`;
        nameInput.value = name;
        
        modal.classList.remove('hidden');
    }
</script>
@endsection
