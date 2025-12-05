<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AFM - Development Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-2xl w-full">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-2">نظام إدارة التقييمات الأكاديمية</h1>
            <h2 class="text-2xl text-gray-600">Academic Feedback Management (AFM)</h2>
            <div class="mt-4 inline-block bg-yellow-100 border-2 border-yellow-400 rounded-lg px-4 py-2">
                <p class="text-yellow-800 font-semibold">🔧 وضع التطوير المحلي</p>
                <p class="text-sm text-yellow-700">Development Mode - Local Environment Only</p>
            </div>
        </div>

        <!-- Dev Login Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Student Login -->
            <a href="{{ route('dev.login.student') }}" class="bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 p-6 text-center hover:scale-105">
                <div class="text-6xl mb-4">👨‍🎓</div>
                <h3 class="text-xl font-bold text-blue-600 mb-2">طالب</h3>
                <p class="text-gray-600 text-sm mb-4">Student</p>
                <div class="bg-blue-50 rounded-lg p-3 text-xs text-right">
                    <p class="font-mono">ID: 4401</p>
                    <p class="text-gray-700">Nahla Burweiss</p>
                    <p class="text-gray-500">Term: 202410</p>
                </div>
                <button class="mt-4 w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg transition">
                    تسجيل الدخول
                </button>
            </a>

            <!-- QA Officer Login -->
            <a href="{{ route('dev.login.qa') }}" class="bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 p-6 text-center hover:scale-105">
                <div class="text-6xl mb-4">👔</div>
                <h3 class="text-xl font-bold text-green-600 mb-2">ضمان الجودة</h3>
                <p class="text-gray-600 text-sm mb-4">QA Officer</p>
                <div class="bg-green-50 rounded-lg p-3 text-xs text-right">
                    <p class="font-mono">ID: qa001</p>
                    <p class="text-gray-700">Dr. Ahmed QA</p>
                    <p class="text-gray-500">Term: 202410</p>
                </div>
                <button class="mt-4 w-full bg-green-600 hover:bg-green-700 text-white py-2 rounded-lg transition">
                    تسجيل الدخول
                </button>
            </a>

            <!-- Admin Login -->
            <a href="{{ route('dev.login.admin') }}" class="bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 p-6 text-center hover:scale-105">
                <div class="text-6xl mb-4">⚙️</div>
                <h3 class="text-xl font-bold text-purple-600 mb-2">مسؤول النظام</h3>
                <p class="text-gray-600 text-sm mb-4">Administrator</p>
                <div class="bg-purple-50 rounded-lg p-3 text-xs text-right">
                    <p class="font-mono">ID: admin001</p>
                    <p class="text-gray-700">System Admin</p>
                    <p class="text-gray-500">Full Access</p>
                </div>
                <button class="mt-4 w-full bg-purple-600 hover:bg-purple-700 text-white py-2 rounded-lg transition">
                    تسجيل الدخول
                </button>
            </a>
        </div>

        <!-- Info Panel -->
        <div class="bg-white rounded-2xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                <span class="text-2xl ml-2">ℹ️</span>
                معلومات التطوير
            </h3>
            <div class="space-y-3 text-sm text-gray-700">
                <div class="flex items-start">
                    <span class="text-blue-600 font-bold ml-2">📌</span>
                    <div>
                        <strong>Session Keys:</strong> All dev logins set the correct session structure 
                        (<code class="bg-gray-100 px-1 rounded">afm_token_id</code>, 
                        <code class="bg-gray-100 px-1 rounded">afm_role</code>, 
                        <code class="bg-gray-100 px-1 rounded">afm_user_id</code>, etc.)
                    </div>
                </div>
                <div class="flex items-start">
                    <span class="text-green-600 font-bold ml-2">🔐</span>
                    <div>
                        <strong>Security:</strong> These routes only work in <code class="bg-gray-100 px-1 rounded">APP_ENV=local</code>. 
                        Production deployments will return 404.
                    </div>
                </div>
                <div class="flex items-start">
                    <span class="text-purple-600 font-bold ml-2">🚪</span>
                    <div>
                        <strong>Logout:</strong> Use 
                        <a href="{{ route('dev.logout') }}" class="text-blue-600 hover:underline font-semibold">/dev/logout</a> 
                        to clear all AFM session keys.
                    </div>
                </div>
                <div class="flex items-start">
                    <span class="text-orange-600 font-bold ml-2">📚</span>
                    <div>
                        <strong>SSO in Production:</strong> Real users access via 
                        <code class="bg-gray-100 px-1 rounded">/sso/intake</code> → 
                        <code class="bg-gray-100 px-1 rounded">/sso/handshake/{'{tokenId}'}</code>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8 text-gray-500 text-sm">
            <p>Libya International Medical University</p>
            <p class="mt-1">© 2025 AFM Rebuild - Development Environment</p>
        </div>
    </div>
</body>
</html>
