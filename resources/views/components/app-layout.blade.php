<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>AFM – Academic Feedback Module</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 antialiased">
    <div class="min-h-screen flex flex-col">
        {{-- Top nav (simple AFM bar) --}}
        <header class="bg-slate-900 text-white">
            <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-sky-500 text-white font-bold text-xs">
                        AFM
                    </span>
                    <span class="font-semibold">Academic Feedback Module</span>
                </div>
                <div class="text-xs opacity-80">
                    Session expires: <span id="session-countdown">--:--</span>
                </div>
            </div>
        </header>

        {{-- Content --}}
        <main class="flex-1">
            {{ $slot }}
        </main>

        <footer class="border-t bg-white">
            <div class="max-w-7xl mx-auto px-4 py-3 text-xs text-slate-500 flex justify-between">
                <span>© 2024 University SIS Integration. All rights reserved.</span>
                <span>Ver 2.5.0 (Prototype)</span>
            </div>
        </footer>
    </div>
</body>
</html>
