<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AFM Dev Simulator</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-3xl font-bold mb-6 text-gray-800">AFM Development Simulator (JSON SSO)</h1>
        
        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 gap-6">
            @foreach($payloads as $index => $payload)
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <span class="inline-block px-2 py-1 text-sm font-semibold rounded 
                                {{ $payload['role'] === 'qa' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                                {{ strtoupper($payload['role']) }}
                            </span>
                            <h2 class="text-xl font-bold mt-2">
                                {{ $payload['role'] === 'qa' ? ($payload['user_name'] ?? 'QA') : ($payload['student_Name'] ?? 'Student') }}
                            </h2>
                            <p class="text-sm text-gray-600">ID: {{ $payload['role'] === 'qa' ? $payload['user_id'] : $payload['student_id'] }}</p>
                        </div>
                        <form action="{{ route('dev.simulator.login') }}" method="POST">
                            @csrf
                            <input type="hidden" name="json_payload" value="{{ json_encode($payload) }}">
                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded transition">
                                Login with this JSON
                            </button>
                        </form>
                    </div>
                    
                    <textarea class="w-full h-48 p-2 font-mono text-xs bg-gray-50 border rounded" readonly>{{ json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</textarea>
                </div>
            @endforeach
        </div>
    </div>
</body>
</html>
