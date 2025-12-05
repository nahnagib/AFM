@extends('layouts.app')

@section('title', 'سجل التدقيق')

@section('content')
<div class="max-w-7xl mx-auto">
    <h1 class="text-3xl font-bold text-gray-800 mb-6">سجل التدقيق</h1>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow border border-slate-200 p-4 mb-6">
        <form method="GET" class="grid grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">نوع الحدث</label>
                <select name="event_type" class="w-full border border-gray-300 rounded px-3 py-2">
                    <option value="">الكل</option>
                    <option value="form">نماذج</option>
                    <option value="response">استجابات</option>
                    <option value="completion">إكمال</option>
                    <option value="notification">إشعارات</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">معرف الفاعل</label>
                <input type="text" name="actor_id" class="w-full border border-gray-300 rounded px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">من تاريخ</label>
                <input type="date" name="from_date" class="w-full border border-gray-300 rounded px-3 py-2">
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded transition">
                    تطبيق
                </button>
            </div>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="bg-white rounded-lg shadow border border-slate-200 overflow-hidden">
        <table class="min-w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">التاريخ</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">النوع</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">الإجراء</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">الفاعل</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">الهدف</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">التفاصيل</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($logs as $log)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $log->created_at->format('Y-m-d H:i') }}</td>
                        <td class="px-6 py-4 text-sm">
                            <span class="px-2 py-1 rounded text-xs bg-gray-100">{{ $log->event_type }}</span>
                        </td>
                        <td class="px-6 py-4 text-sm">{{ $log->action }}</td>
                        <td class="px-6 py-4 text-sm">
                            @if($log->actor_id)
                                <span class="font-mono text-xs">{{ $log->actor_id }}</span>
                                <br><span class="text-xs text-gray-500">{{ $log->actor_role }}</span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm">
                            @if($log->target_id)
                                {{ $log->target_type }} #{{ $log->target_id }}
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <a href="{{ route('admin.audit.show', $log->id) }}" class="text-blue-600 hover:text-blue-800">عرض</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">لا توجد سجلات</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $logs->links() }}
    </div>
</div>
@endsection
