@extends('layouts.app')

@section('title', 'إعدادات النظام')

@section('content')
<div class="max-w-4xl mx-auto">
    <h1 class="text-3xl font-bold text-gray-800 mb-6">إعدادات النظام</h1>

    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('admin.config.update') }}" method="POST">
            @csrf
            
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">الفصل الدراسي الحالي</label>
                <input type="text" name="current_term" required 
                       value="{{ old('current_term', $config['current_term']) }}"
                       class="w-full border border-gray-300 rounded px-3 py-2">
                <p class="text-xs text-gray-500 mt-1">مثال: 202410</p>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">عتبة المقررات عالية الخطورة</label>
                <input type="number" name="high_risk_threshold" required step="0.01" min="0" max="1"
                       value="{{ old('high_risk_threshold', $config['high_risk_threshold']) }}"
                       class="w-full border border-gray-300 rounded px-3 py-2">
                <p class="text-xs text-gray-500 mt-1">نسبة الإكمال الأدنى (0.0 - 1.0). مثال: 0.6 تعني 60%</p>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">فترة الحفظ التلقائي (بالثواني)</label>
                <input type="number" name="auto_save_interval" required min="10" max="300"
                       value="{{ old('auto_save_interval', $config['auto_save_interval']) }}"
                       class="w-full border border-gray-300 rounded px-3 py-2">
                <p class="text-xs text-gray-500 mt-1">الوقت بين عمليات الحفظ التلقائي للمسودات (10-300 ثانية)</p>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded transition">
                    💾 حفظ الإعدادات
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
