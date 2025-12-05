<x-qa-layout>
    <div class="max-w-7xl mx-auto">
        <div class="md:flex md:items-center md:justify-between mb-6">
            <div class="flex-1 min-w-0">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                    Manage Scope: {{ $form->title }}
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Form Type: {{ ucfirst(str_replace('_', ' ', $form->form_type)) }}
                </p>
            </div>
            <div class="mt-4 flex md:mt-0 md:ml-4">
                <a href="{{ route('qa.forms.edit', $form) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    Back to Form
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
            <div class="flex space-x-4">
                <button type="button" id="assignAllBtn" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                    Assign to All Courses (Current Term)
                </button>
                <button type="button" id="addScopeBtn" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Add Individual Scope
                </button>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Course
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Term
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Required
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" id="scopesTable">
                    @forelse($scopes as $scope)
                        <tr data-scope-id="{{ $scope->id }}">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $scope->course_reg_no ?? 'All Courses (Service)' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $scope->term_code }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $scope->is_required ? 'Yes' : 'No' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button type="button" class="delete-scope text-red-600 hover:text-red-900">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                No scopes assigned yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Scope Modal -->
    <div id="scopeModal" class="hidden fixed z-10 inset-0 overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Add Scope</h3>
                    <div class="space-y-4">
                        @if($form->form_type === 'course_feedback')
                            <div>
                                <label for="courseSelect" class="block text-sm font-medium text-gray-700">Course</label>
                                <select id="courseSelect" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="">Select a course</option>
                                    @foreach($availableCourses as $course)
                                        <option value="{{ $course->course_reg_no }}">{{ $course->course_reg_no }} - {{ $course->course_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div>
                            <label for="termCode" class="block text-sm font-medium text-gray-700">Term Code</label>
                            <input type="text" id="termCode" value="202410" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" id="isRequired" checked class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                <span class="ml-2 text-sm text-gray-700">Required</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" id="saveScopeBtn" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto sm:text-sm">
                        Save
                    </button>
                    <button type="button" id="cancelScopeBtn" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const formId = {{ $form->id }};
        const formType = '{{ $form->form_type }}';
        const csrfToken = '{{ csrf_token() }}';

        document.getElementById('addScopeBtn').addEventListener('click', function() {
            document.getElementById('scopeModal').classList.remove('hidden');
        });

        document.getElementById('cancelScopeBtn').addEventListener('click', function() {
            document.getElementById('scopeModal').classList.add('hidden');
        });

        document.getElementById('saveScopeBtn').addEventListener('click', async function() {
            const termCode = document.getElementById('termCode').value;
            const isRequired = document.getElementById('isRequired').checked;
            
            const data = {
                term_code: termCode,
                is_required: isRequired
            };

            if (formType === 'course_feedback') {
                const courseRegNo = document.getElementById('courseSelect').value;
                if (!courseRegNo) {
                    alert('Please select a course');
                    return;
                }
                data.course_reg_no = courseRegNo;
            }

            const response = await fetch(`/qa/forms/${formId}/scope`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(data)
            });

            if (response.ok) {
                location.reload();
            }
        });

        document.getElementById('assignAllBtn').addEventListener('click', async function() {
            if (!confirm('This will assign this form to all courses in the current term. Continue?')) return;

            const response = await fetch(`/qa/forms/${formId}/scope/assign-all`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ term_code: '202410' })
            });

            if (response.ok) {
                location.reload();
            }
        });

        document.addEventListener('click', async function(e) {
            if (e.target.classList.contains('delete-scope')) {
                if (!confirm('Are you sure you want to delete this scope?')) return;
                const row = e.target.closest('tr');
                const scopeId = row.dataset.scopeId;
                const response = await fetch(`/qa/scope/${scopeId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    }
                });
                if (response.ok) {
                    location.reload();
                }
            }
        });
    </script>
</x-qa-layout>
