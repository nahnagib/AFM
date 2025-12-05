<x-qa-layout>
    <div class="space-y-6">
        <!-- Header -->
        <div class="sm:flex sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Student List & Exports</h1>
                <p class="mt-2 text-sm text-gray-700">View and export student feedback completion status</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <form method="GET" action="{{ route('qa.students') }}" class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <!-- Form Type (Required) -->
                        <div>
                            <label for="form_type" class="block text-sm font-medium text-gray-700">Form Type *</label>
                            <select id="form_type" name="form_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" onchange="this.form.submit()">
                                <option value="course" {{ $filters['form_type'] === 'course' ? 'selected' : '' }}>Course Feedback</option>
                                <option value="system" {{ $filters['form_type'] === 'system' ? 'selected' : '' }}>System & Services</option>
                            </select>
                        </div>

                        <!-- Course Filter (only for course type) -->
                        @if($filters['form_type'] === 'course')
                        <div>
                            <label for="course" class="block text-sm font-medium text-gray-700">Course</label>
                            <select id="course" name="course" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">All Courses</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course->course_reg_no }}" {{ $filters['course'] === $course->course_reg_no ? 'selected' : '' }}>
                                        {{ $course->course_code }} - {{ $course->course_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        <!-- Template Filter -->
                        <div>
                            <label for="template" class="block text-sm font-medium text-gray-700">Form Template</label>
                            <select id="template" name="template" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">All Templates</option>
                                @foreach($templates as $template)
                                    <option value="{{ $template->id }}" {{ $filters['template'] == $template->id ? 'selected' : '' }}>
                                        {{ $template->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                            <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="all" {{ $filters['status'] === 'all' ? 'selected' : '' }}>All</option>
                                <option value="not_started" {{ $filters['status'] === 'not_started' ? 'selected' : '' }}>Not Started</option>
                                <option value="in_progress" {{ $filters['status'] === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                <option value="completed" {{ $filters['status'] === 'completed' ? 'selected' : '' }}>Completed</option>
                            </select>
                        </div>
                    </div>

                    <!-- Search -->
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <label for="search" class="block text-sm font-medium text-gray-700">Search by Student ID or Name</label>
                            <input type="text" name="search" id="search" value="{{ $filters['search'] }}" placeholder="Enter student ID or name..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div class="flex items-end gap-2">
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Apply Filters
                            </button>
                            <a href="{{ route('qa.students') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Clear
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Export Buttons -->
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-4 sm:px-6 flex justify-between items-center">
                <div class="text-sm text-gray-700">
                    Showing {{ $responses->count() }} of {{ $responses->total() }} results
                </div>
                <div class="flex gap-2">
                    <form method="POST" action="{{ route('qa.students.export') }}" class="inline">
                        @csrf
                        <input type="hidden" name="format" value="pdf">
                        @foreach($filters as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach
                        <button type="submit" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                            Export PDF
                        </button>
                    </form>
                    <form method="POST" action="{{ route('qa.students.export') }}" class="inline">
                        @csrf
                        <input type="hidden" name="format" value="xlsx">
                        @foreach($filters as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach
                        <button type="submit" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                            Export Excel
                        </button>
                    </form>
                    <form method="POST" action="{{ route('qa.students.export') }}" class="inline">
                        @csrf
                        <input type="hidden" name="format" value="csv">
                        @foreach($filters as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach
                        <button type="submit" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Export CSV
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Data Table -->
        <div class="bg-white shadow sm:rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Student ID
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Student Name
                            </th>
                            @if($filters['form_type'] === 'course')
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Course
                            </th>
                            @endif
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Form
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Last Active
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($responses as $response)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $response->sis_student_id }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $response->student_name }}
                            </td>
                            @if($filters['form_type'] === 'course')
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <div>{{ $response->course_code }}</div>
                                <div class="text-xs text-gray-500">{{ $response->course_name }}</div>
                            </td>
                            @endif
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <div>{{ $response->form_title }}</div>
                                <div class="text-xs text-gray-500">{{ $response->form_code }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $response->status === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $response->status === 'in_progress' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                    {{ $response->status === 'not_started' ? 'bg-gray-100 text-gray-800' : '' }}">
                                    {{ ucfirst(str_replace('_', ' ', $response->status)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $response->last_active_at ? $response->last_active_at->format('Y-m-d H:i') : 'Never' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500">
                                No results found. Try adjusting your filters.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($responses->hasPages())
            <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                {{ $responses->links() }}
            </div>
            @endif
        </div>
    </div>
</x-qa-layout>
