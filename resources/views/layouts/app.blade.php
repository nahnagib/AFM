<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Academic Feedback System')</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .fade-in { animation: fadeIn 0.3s ease-in; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    </style>
    @stack('styles')
</head>

<body class="bg-gray-50 text-slate-800 font-sans antialiased">
    @include('layouts.partials.nav')
    @include('layouts.partials.flash')
    <main class="container mx-auto px-4 py-8 min-h-[calc(100vh-200px)]">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 mt-12 py-8">
        <div class="container mx-auto px-4 text-center text-slate-500 text-sm">
            <p class="font-medium">Academic Feedback Management System &copy; {{ date('Y') }}</p>
            <p class="mt-1">Libya International Medical University</p>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
