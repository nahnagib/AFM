@extends('layouts.app')

@section('title', 'Staff Management')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Staff Management</h1>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Sidebar: Roles -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-4">
            <h3 class="text-lg font-semibold mb-4 text-slate-700 dark:text-slate-200">Roles</h3>
            <ul class="space-y-2">
                @foreach($roles as $role)
                    <li>
                        <a href="{{ route('qa.staff.index', ['role' => $role->role_key]) }}" 
                           class="block px-4 py-2 rounded-md transition {{ $selectedRole === $role->role_key ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200' : 'hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300' }}">
                            {{ $role->label_ar }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>

        <!-- Main Content: Staff List -->
        <div class="lg:col-span-3">
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold text-slate-800 dark:text-white">
                        @if($selectedRole)
                            {{ $roles->where('role_key', $selectedRole)->first()->label_ar }}
                        @else
                            Select a Role
                        @endif
                    </h2>
                    @if($selectedRole)
                        <button onclick="document.getElementById('addStaffModal').classList.remove('hidden')" 
                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition">
                            Add Staff Member
                        </button>
                    @endif
                </div>

                @if($staffMembers->isEmpty())
                    <div class="text-center py-12 text-slate-500 dark:text-slate-400">
                        No staff members found for this role.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-slate-200 dark:border-slate-700">
                                    <th class="py-3 px-4 text-sm font-semibold text-slate-600 dark:text-slate-300">Name (Arabic)</th>
                                    <th class="py-3 px-4 text-sm font-semibold text-slate-600 dark:text-slate-300">Status</th>
                                    <th class="py-3 px-4 text-sm font-semibold text-slate-600 dark:text-slate-300 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($staffMembers as $staff)
                                    <tr class="border-b border-slate-100 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-750 transition">
                                        <td class="py-3 px-4 text-slate-800 dark:text-slate-200">{{ $staff->name_ar }}</td>
                                        <td class="py-3 px-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $staff->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                                                {{ $staff->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-right space-x-2">
                                            <button onclick="editStaff({{ $staff->id }}, '{{ $staff->name_ar }}')" 
                                                    class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium">
                                                Edit
                                            </button>
                                            <form action="{{ route('qa.staff.toggle', $staff->id) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 text-sm font-medium">
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
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl w-full max-w-md p-6">
        <h3 class="text-lg font-bold mb-4 text-slate-900 dark:text-white">Add New Staff Member</h3>
        <form action="{{ route('qa.staff.store') }}" method="POST">
            @csrf
            <input type="hidden" name="staff_role_id" value="{{ $roles->where('role_key', $selectedRole)->first()->id ?? '' }}">
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Name (Arabic)</label>
                <input type="text" name="name_ar" required class="w-full rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="document.getElementById('addStaffModal').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-md">Cancel</button>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md">Add Staff</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Staff Modal -->
<div id="editStaffModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl w-full max-w-md p-6">
        <h3 class="text-lg font-bold mb-4 text-slate-900 dark:text-white">Edit Staff Member</h3>
        <form id="editStaffForm" method="POST">
            @csrf
            @method('PUT')
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Name (Arabic)</label>
                <input type="text" id="editNameAr" name="name_ar" required class="w-full rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="document.getElementById('editStaffModal').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-md">Cancel</button>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md">Update Staff</button>
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
