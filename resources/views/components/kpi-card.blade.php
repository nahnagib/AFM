<div class="bg-white rounded-lg shadow p-6">
    <div class="flex items-center justify-between mb-2">
        <h3 class="text-lg font-semibold text-gray-700">{{ $title }}</h3>
        @isset($icon)
            <div class="text-3xl">{{ $icon }}</div>
        @endisset
    </div>
    <p class="text-3xl font-bold {{ $color ?? 'text-blue-600' }}">{{ $value }}</p>
    @isset($subtitle)
        <p class="text-sm text-gray-500 mt-1">{{ $subtitle }}</p>
    @endisset
</div>
