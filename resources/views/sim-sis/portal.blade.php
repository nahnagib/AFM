<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIS Simulator - LIMU</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-lg max-w-md w-full">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-indigo-900">LIMU SIS Simulator</h1>
            <p class="text-gray-600 mt-2">Student Information System Portal</p>
        </div>

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        <form action="{{ url('/sim-sis/launch') }}" method="POST" class="space-y-6">
            @csrf
            <div>
                <label for="student_id" class="block text-sm font-medium text-gray-700">Select Student</label>
                <select id="student_id" name="student_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md border">
                    @foreach($students as $student)
                        <option value="{{ $student->student_id }}">
                            {{ $student->full_name }} ({{ $student->student_id }}) - {{ $student->department }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="bg-gray-50 p-4 rounded-md">
                <h3 class="text-sm font-medium text-gray-900">Integration Details</h3>
                <ul class="mt-2 text-xs text-gray-500 list-disc list-inside">
                    <li>Generates HMAC-SHA256 signed payload</li>
                    <li>Includes enrolled courses</li>
                    <li>Redirects to AFM Dashboard</li>
                </ul>
            </div>

            <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Open AFM Feedback
            </button>
        </form>
        
        <div class="mt-6">
            <form action="{{ route('sim-sis.launch-qa') }}" method="POST">
                @csrf
                <button type="submit" class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Go to QA Dashboard (Simulated SSO)
                </button>
            </form>
        </div>
    </div>
</body>
</html>
