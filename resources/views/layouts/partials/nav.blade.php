<nav class="bg-slate-900 text-white shadow-lg border-b border-slate-800">
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between h-16">
            <div class="flex items-center space-x-6">
                <a href="{{ url('/afm') }}" class="text-xl font-bold tracking-tight flex items-center gap-2">
                    <span class="text-blue-400">AFM</span> System
                </a>

                {{-- Theme toggle button – IDs must match the script --}}

                {{-- …other nav items… (keep existing links) --}}
                @if(session('afm_role'))
                    <div class="hidden md:flex space-x-1">
                        @if(session('afm_role') === 'student')
                            <a href="{{ route('student.dashboard') }}" class="hover:bg-slate-800 px-3 py-2 rounded-md text-sm font-medium transition {{ request()->routeIs('student.dashboard') ? 'bg-slate-800 text-blue-400' : 'text-slate-300' }}">Dashboard</a>
                        @elseif(session('afm_role') === 'qa_officer')
                            <a href="{{ route('qa.overview') }}" class="hover:bg-slate-800 px-3 py-2 rounded-md text-sm font-medium transition {{ request()->routeIs('qa.overview') ? 'bg-slate-800 text-blue-400' : 'text-slate-300' }}">Overview</a>
                            <a href="{{ route('qa.forms.index') }}" class="hover:bg-slate-800 px-3 py-2 rounded-md text-sm font-medium transition {{ request()->routeIs('qa.forms.*') ? 'bg-slate-800 text-blue-400' : 'text-slate-300' }}">Forms</a>
                            <a href="{{ route('qa.reports.completion') }}" class="hover:bg-slate-800 px-3 py-2 rounded-md text-sm font-medium transition {{ request()->routeIs('qa.reports.*') ? 'bg-slate-800 text-blue-400' : 'text-slate-300' }}">Reports</a>
                            <a href="{{ route('qa.reminders.index') }}" class="hover:bg-slate-800 px-3 py-2 rounded-md text-sm font-medium transition {{ request()->routeIs('qa.reminders.*') ? 'bg-slate-800 text-blue-400' : 'text-slate-30' }}">Reminders</a>
                        @elseif(session('afm_role') === 'admin')
                            <a href="{{ route('admin.config.index') }}" class="hover:bg-slate-800 px-3 py-2 rounded-md text-sm font-medium transition {{ request()->routeIs('admin.config.*') ? 'bg-slate-800 text-blue-400' : 'text-slate-300' }}">Settings</a>
                            <a href="{{ route('admin.audit.index') }}" class="hover:bg-slate-800 px-3 py-2 rounded-md text-sm font-medium transition {{ request()->routeIs('admin.audit.*') ? 'bg-slate-800 text-blue-400' : 'text-slate-300' }}">Audit Log</a>
                        @endif
                    </div>
                @endif
            </div>

            <div class="flex items-center space-x-4">
                @if(session('afm_user_name'))
                    <div class="flex items-center gap-3">
                        <div class="text-right hidden sm:block">
                            <div class="text-sm font-medium text-white">{{ session('afm_user_name') }}</div>
                            @if(session('afm_role'))
                                <div class="text-xs text-slate-400 uppercase tracking-wider">
                                    @if(session('afm_role') === 'student') Student
                                    @elseif(session('afm_role') === 'qa_officer') QA Officer
                                    @elseif(session('afm_role') === 'admin') Administrator
                                    @endif
                                </div>
                            @endif
                        </div>
                        <a href="{{ app()->environment('local') ? route('dev.logout') : url('/afm/logout') }}" class="bg-slate-800 hover:bg-red-600 text-slate-300 hover:text-white p-2 rounded-full transition duration-200" title="Logout">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</nav>
