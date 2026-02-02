<!DOCTYPE html>
<html lang="en" x-data="{ darkMode: localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches) }" x-bind:class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Taskboard - Kanban</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@100..700&display=swap" rel="stylesheet">
    @vite(['resources/js/app.js'])
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#13b6ec",
                        "background-light": "#f6f8f8",
                        "background-dark": "#101d22",
                        "card-dark": "#192d33",
                        "card-border-dark": "#233f48",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    },
                },
            },
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none !important; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .line-clamp-3 { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
        .task-card { cursor: grab; }
        .task-card:active { cursor: grabbing; }
        .sortable-ghost { opacity: 0.4; border: 2px dashed #13b6ec; background: rgba(19, 182, 236, 0.1) !important; }
        .sortable-chosen { box-shadow: 0 8px 25px rgba(0,0,0,0.2); }
        .sortable-drag, .sortable-fallback { opacity: 0.95; transform: rotate(1deg); box-shadow: 0 12px 35px rgba(0,0,0,0.3); }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-slate-900 dark:text-white transition-colors duration-300 min-h-screen" x-data="taskboard()">
    
    <!-- Header -->
    <header class="sticky top-0 z-50 bg-background-light dark:bg-background-dark border-b border-slate-200 dark:border-slate-800">
        <div class="flex items-center px-4 lg:px-8 py-4 justify-between max-w-[1800px] mx-auto">
            <div class="flex items-center gap-3">
                <div class="text-primary flex size-10 shrink-0 items-center justify-center bg-primary/10 rounded-lg">
                    <span class="material-symbols-outlined">dashboard_customize</span>
                </div>
                <h1 class="hidden sm:block text-slate-900 dark:text-white text-xl font-bold leading-tight tracking-tight">Task Board</h1>
            </div>
            <div class="flex items-center gap-4">
                <!-- Tab Switcher -->
                <div class="flex items-center bg-slate-100 dark:bg-slate-800 rounded-lg p-1">
                    <button @click="activeTab = 'kanban'" :class="activeTab === 'kanban' ? 'bg-white dark:bg-card-dark text-primary shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300'" class="flex items-center justify-center gap-1.5 px-4 sm:px-3 py-2.5 sm:py-1.5 rounded-md text-sm font-medium transition-all min-w-[48px]">
                        <span class="material-symbols-outlined text-[20px] sm:text-[18px]">view_kanban</span>
                        <span class="hidden sm:inline">Board</span>
                    </button>
                    <button @click="activeTab = 'activity'; fetchActivityLogs()" :class="activeTab === 'activity' ? 'bg-white dark:bg-card-dark text-primary shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300'" class="flex items-center justify-center gap-1.5 px-4 sm:px-3 py-2.5 sm:py-1.5 rounded-md text-sm font-medium transition-all min-w-[48px]">
                        <span class="material-symbols-outlined text-[20px] sm:text-[18px]">timeline</span>
                        <span class="hidden sm:inline">Activity</span>
                    </button>
                    <button @click="activeTab = 'schedule'; fetchScheduledRoutines()" :class="activeTab === 'schedule' ? 'bg-white dark:bg-card-dark text-primary shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300'" class="flex items-center justify-center gap-1.5 px-4 sm:px-3 py-2.5 sm:py-1.5 rounded-md text-sm font-medium transition-all min-w-[48px]">
                        <span class="material-symbols-outlined text-[20px] sm:text-[18px]">schedule</span>
                        <span class="hidden sm:inline">Schedule</span>
                    </button>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button @click="darkMode = !darkMode; localStorage.setItem('theme', darkMode ? 'dark' : 'light')" 
                        class="flex items-center justify-center w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-primary transition-colors hover:bg-slate-200 dark:hover:bg-slate-700">
                    <span x-show="darkMode" class="material-symbols-outlined text-[20px]">light_mode</span>
                    <span x-show="!darkMode" class="material-symbols-outlined text-[20px]">dark_mode</span>
                </button>
                <form action="{{ route('logout') }}" method="POST" class="hidden sm:inline">
                    @csrf
                    <button type="submit" class="flex items-center justify-center px-4 py-2 rounded-lg bg-red-500/10 text-red-500 hover:bg-red-500/20 transition-colors text-sm font-medium">
                        <span class="material-symbols-outlined text-[18px] mr-1">logout</span>
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </header>

    <!-- Main Kanban Board -->
    <main x-show="activeTab === 'kanban'" class="p-4 lg:p-8 max-w-[1800px] mx-auto">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
            
            <!-- Backlog Column -->
            <div class="flex flex-col bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden">
                <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">📥</span>
                        <h2 class="font-bold text-slate-900 dark:text-white">Backlog</h2>
                        <span class="bg-primary/20 text-primary text-[10px] px-2 py-0.5 rounded-full font-bold" x-text="getColumnTasks('backlog').length"></span>
                    </div>
                    <button @click="openAddTask('backlog')" class="text-primary hover:text-primary/80 transition-colors">
                        <span class="material-symbols-outlined">add_circle</span>
                    </button>
                </div>
                <div class="p-3 space-y-3 min-h-[400px] max-h-[calc(100vh-280px)] overflow-y-auto no-scrollbar sortable-column" data-column="backlog" x-ref="col_backlog"></div>
            </div>

            <!-- To Do Column -->
            <div class="flex flex-col bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden">
                <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">📋</span>
                        <h2 class="font-bold text-slate-900 dark:text-white">To Do</h2>
                        <span class="bg-blue-500/20 text-blue-500 text-[10px] px-2 py-0.5 rounded-full font-bold" x-text="getColumnTasks('todo').length"></span>
                    </div>
                    <button @click="openAddTask('todo')" class="text-primary hover:text-primary/80 transition-colors">
                        <span class="material-symbols-outlined">add_circle</span>
                    </button>
                </div>
                <div class="p-3 space-y-3 min-h-[400px] max-h-[calc(100vh-280px)] overflow-y-auto no-scrollbar sortable-column" data-column="todo" x-ref="col_todo"></div>
            </div>

            <!-- In Progress Column -->
            <div class="flex flex-col bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden">
                <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">⚡</span>
                        <h2 class="font-bold text-slate-900 dark:text-white">In Progress</h2>
                        <span class="bg-yellow-500/20 text-yellow-500 text-[10px] px-2 py-0.5 rounded-full font-bold" x-text="getColumnTasks('in_progress').length"></span>
                    </div>
                    <button @click="openAddTask('in_progress')" class="text-primary hover:text-primary/80 transition-colors">
                        <span class="material-symbols-outlined">add_circle</span>
                    </button>
                </div>
                <div class="p-3 space-y-3 min-h-[400px] max-h-[calc(100vh-280px)] overflow-y-auto no-scrollbar sortable-column" data-column="in_progress" x-ref="col_in_progress"></div>
            </div>

            <!-- Done Column -->
            <div class="flex flex-col bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden">
                <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">✅</span>
                        <h2 class="font-bold text-slate-900 dark:text-white">Done</h2>
                        <span class="bg-emerald-500/20 text-emerald-500 text-[10px] px-2 py-0.5 rounded-full font-bold" x-text="getColumnTasks('done').length"></span>
                    </div>
                    <button @click="openAddTask('done')" class="text-primary hover:text-primary/80 transition-colors">
                        <span class="material-symbols-outlined">add_circle</span>
                    </button>
                </div>
                <div class="p-3 space-y-3 min-h-[400px] max-h-[calc(100vh-280px)] overflow-y-auto no-scrollbar sortable-column" data-column="done" x-ref="col_done"></div>
            </div>

        </div>
    </main>

    <!-- Activity Log View -->
    <div x-show="activeTab === 'activity'" x-cloak class="p-4 lg:p-8 max-w-[1200px] mx-auto">
        <!-- Activity Header -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
            <div>
                <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">timeline</span>
                    Alex's Activity Log
                </h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Track emails, SMS, fixes, and more</p>
            </div>
            <div class="flex items-center gap-3 w-full sm:w-auto">
                <input type="date" x-model="activityDateFilter" @change="fetchActivityLogs()" class="flex-1 sm:flex-none px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-card-dark text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-primary/50">
                <select x-model="activityTypeFilter" @change="fetchActivityLogs()" class="flex-1 sm:flex-none px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-card-dark text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-primary/50">
                    <option value="">All Types</option>
                    <option value="email">📧 Email</option>
                    <option value="sms">📱 SMS</option>
                    <option value="order_fix">🛒 Order Fix</option>
                    <option value="analysis">📊 Analysis</option>
                    <option value="integration">🔌 Integration</option>
                    <option value="other">📝 Other</option>
                </select>
                <button @click="activityDateFilter = ''; activityTypeFilter = ''; fetchActivityLogs()" class="p-2 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" title="Clear filters">
                    <span class="material-symbols-outlined text-[20px]">filter_alt_off</span>
                </button>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="activityLoading" class="flex items-center justify-center py-20">
            <div class="flex items-center gap-3 text-slate-500 dark:text-slate-400">
                <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Loading activity...</span>
            </div>
        </div>

        <!-- Empty State -->
        <div x-show="!activityLoading && activityGroups.length === 0" class="flex flex-col items-center justify-center py-20 text-center">
            <span class="material-symbols-outlined text-[64px] text-slate-300 dark:text-slate-600 mb-4">event_note</span>
            <h3 class="text-lg font-semibold text-slate-500 dark:text-slate-400 mb-1">No activity yet</h3>
            <p class="text-sm text-slate-400 dark:text-slate-500">Activity logs will appear here as Alex works</p>
        </div>

        <!-- Timeline -->
        <div x-show="!activityLoading && activityGroups.length > 0" class="space-y-8">
            <template x-for="group in activityGroups" :key="group.date">
                <div>
                    <!-- Date Header -->
                    <div class="flex items-center gap-3 mb-4">
                        <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-primary/10 text-primary text-sm font-semibold">
                            <span class="material-symbols-outlined text-[16px]">calendar_today</span>
                            <span x-text="group.dateLabel"></span>
                        </div>
                        <div class="flex-1 h-px bg-slate-200 dark:bg-slate-700"></div>
                        <span class="text-xs text-slate-400 dark:text-slate-500" x-text="group.items.length + ' entries'"></span>
                    </div>

                    <!-- Activity Items -->
                    <div class="space-y-3 ml-2 border-l-2 border-slate-200 dark:border-slate-700 pl-6 relative">
                        <template x-for="item in group.items" :key="item.id">
                            <div class="relative group">
                                <!-- Timeline dot -->
                                <div class="absolute -left-[31px] top-3 size-4 rounded-full border-2 border-white dark:border-background-dark" :class="getActivityDotColor(item.type)"></div>
                                <!-- Card -->
                                <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 p-4 transition-all hover:shadow-md">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex items-start gap-3 flex-1 min-w-0">
                                            <span class="text-xl flex-shrink-0 mt-0.5" x-text="getActivityIcon(item.type)"></span>
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center gap-2 mb-1 flex-wrap">
                                                    <h4 class="font-semibold text-slate-900 dark:text-white text-sm" x-text="item.title"></h4>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider" :class="getActivityBadgeClass(item.type)" x-text="item.type.replace('_', ' ')"></span>
                                                </div>
                                                <p x-show="item.description" class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed" x-text="item.description"></p>
                                            </div>
                                        </div>
                                        <span class="text-xs text-slate-400 dark:text-slate-500 whitespace-nowrap flex-shrink-0" x-text="formatActivityTime(item.created_at)"></span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        <!-- Load More -->
        <div x-show="!activityLoading && activityMeta.current_page < activityMeta.last_page" class="flex justify-center mt-8">
            <button @click="loadMoreActivity()" class="px-6 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-sm font-medium hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                Load More
            </button>
        </div>
    </div>

    <!-- Schedule View -->
    <div x-show="activeTab === 'schedule'" x-cloak class="p-4 lg:p-8 max-w-[1200px] mx-auto">
        <!-- Schedule Header -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
            <div>
                <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">schedule</span>
                    Alex's Daily Schedule
                </h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    <span x-text="scheduledRoutines.length"></span> routines configured
                </p>
            </div>
            <div class="flex items-center gap-3 w-full sm:w-auto">
                <select x-model="scheduleCategoryFilter" @change="fetchScheduledRoutines()" class="flex-1 sm:flex-none px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-card-dark text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-primary/50">
                    <option value="">All Categories</option>
                    <option value="email">📧 Email</option>
                    <option value="sms">📱 SMS</option>
                    <option value="orders">🛒 Orders</option>
                    <option value="analysis">📊 Analysis</option>
                    <option value="monitoring">👁 Monitoring</option>
                    <option value="other">📝 Other</option>
                </select>
                <button @click="scheduleCategoryFilter = ''; fetchScheduledRoutines()" class="p-2 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" title="Clear filter">
                    <span class="material-symbols-outlined text-[20px]">filter_alt_off</span>
                </button>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="scheduleLoading" class="flex items-center justify-center py-20">
            <div class="flex items-center gap-3 text-slate-500 dark:text-slate-400">
                <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Loading schedule...</span>
            </div>
        </div>

        <!-- Empty State -->
        <div x-show="!scheduleLoading && scheduledRoutines.length === 0" class="flex flex-col items-center justify-center py-20 text-center">
            <span class="material-symbols-outlined text-[64px] text-slate-300 dark:text-slate-600 mb-4">event_busy</span>
            <h3 class="text-lg font-semibold text-slate-500 dark:text-slate-400 mb-1">No routines scheduled</h3>
            <p class="text-sm text-slate-400 dark:text-slate-500">Add routines via the API to see them here</p>
        </div>

        <!-- Timeline -->
        <div x-show="!scheduleLoading && scheduledRoutines.length > 0" class="space-y-3 sm:ml-2 sm:border-l-2 border-slate-200 dark:border-slate-700 sm:pl-6 relative">
            <template x-for="routine in scheduledRoutines" :key="routine.id">
                <div class="relative group">
                    <!-- Timeline dot (green=enabled, gray=disabled) - hidden on mobile -->
                    <div class="hidden sm:block absolute -left-[31px] top-4 size-4 rounded-full border-2 border-white dark:border-background-dark transition-colors" :class="routine.enabled ? 'bg-emerald-500' : 'bg-slate-400'"></div>
                    <!-- Card -->
                    <div class="bg-white dark:bg-card-dark rounded-xl border border-slate-200 dark:border-slate-800 p-4 transition-all hover:shadow-md" :class="!routine.enabled && 'opacity-50'">
                        <!-- Mobile layout -->
                        <div class="sm:hidden">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <span class="text-lg" x-text="getScheduleIcon(routine.category)"></span>
                                    <span class="text-sm font-bold text-primary" x-text="routine.schedule_time"></span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-primary/10 text-primary" x-text="getFrequencyBadge(routine)"></span>
                                </div>
                                <button @click="toggleRoutine(routine)" class="flex-shrink-0 relative inline-flex h-6 w-11 items-center rounded-full transition-colors" :class="routine.enabled ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-600'">
                                    <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform shadow-sm" :class="routine.enabled ? 'translate-x-6' : 'translate-x-1'"></span>
                                </button>
                            </div>
                            <h4 class="font-semibold text-slate-900 dark:text-white text-sm mb-1" x-text="routine.title"></h4>
                            <p x-show="routine.description" class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed" x-text="routine.description"></p>
                        </div>
                        <!-- Desktop layout -->
                        <div class="hidden sm:flex items-start justify-between gap-3">
                            <div class="flex items-start gap-3 flex-1 min-w-0">
                                <!-- Time -->
                                <div class="flex-shrink-0 text-center min-w-[60px]">
                                    <span class="text-lg font-bold text-slate-900 dark:text-white" x-text="routine.schedule_time"></span>
                                </div>
                                <!-- Category Icon -->
                                <span class="text-xl flex-shrink-0 mt-0.5" x-text="getScheduleIcon(routine.category)"></span>
                                <!-- Content -->
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                                        <h4 class="font-semibold text-slate-900 dark:text-white text-sm" x-text="routine.title"></h4>
                                        <!-- Frequency badge -->
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-primary/10 text-primary" x-text="getFrequencyBadge(routine)"></span>
                                        <!-- Category badge -->
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider" :class="getScheduleBadgeClass(routine.category)" x-text="routine.category"></span>
                                    </div>
                                    <p x-show="routine.description" class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed" x-text="routine.description"></p>
                                </div>
                            </div>
                            <!-- Toggle -->
                            <button @click="toggleRoutine(routine)" class="flex-shrink-0 relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-primary/50 focus:ring-offset-2 dark:focus:ring-offset-background-dark" :class="routine.enabled ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-600'">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform shadow-sm" :class="routine.enabled ? 'translate-x-6' : 'translate-x-1'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Floating Add Button (Mobile) -->
    <button x-show="activeTab === 'kanban'" @click="openAddTask('backlog')" class="fixed bottom-6 right-6 md:hidden size-14 bg-primary text-white rounded-full shadow-lg shadow-primary/40 flex items-center justify-center active:scale-95 transition-transform z-40">
        <span class="material-symbols-outlined text-[32px]">add</span>
    </button>

    <!-- Task Modal -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 transition-opacity">
                <div class="absolute inset-0 bg-slate-900/80 backdrop-blur-sm" @click="closeModal()"></div>
            </div>

            <div x-show="showModal" x-transition class="relative z-10 inline-block align-bottom bg-white dark:bg-card-dark rounded-2xl px-6 pt-6 pb-6 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200 dark:border-slate-800">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">edit_note</span>
                        <span x-text="editingTask ? 'Edit Task' : 'New Task'"></span>
                    </h3>
                    <button @click="closeModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                
                <form @submit.prevent="saveTask()" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-600 dark:text-slate-400 mb-1">Title</label>
                        <input type="text" x-model="formData.title" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-colors" placeholder="Task title..." required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-600 dark:text-slate-400 mb-1">Description</label>
                        <textarea x-model="formData.description" rows="3" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-colors" placeholder="Add details (supports markdown)..."></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-600 dark:text-slate-400 mb-1">Priority</label>
                            <select x-model="formData.priority" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-colors">
                                <option value="low">🟢 Low</option>
                                <option value="medium">🟡 Medium</option>
                                <option value="high">🔴 High</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-600 dark:text-slate-400 mb-1">Assigned To</label>
                            <select x-model="formData.assigned_to" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-colors">
                                <option value="">Unassigned</option>
                                <option value="sandi">👤 Sandi</option>
                                <option value="alex">🤖 Alex</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-600 dark:text-slate-400 mb-1">Due Date</label>
                        <input type="date" x-model="formData.due_date" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-colors">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-600 dark:text-slate-400 mb-1">Tags</label>
                        <input type="text" x-model="tagsInput" @input="updateTags()" placeholder="bug, feature, urgent..." class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-colors">
                        <div x-show="formData.tags && formData.tags.length > 0" class="mt-2 flex flex-wrap gap-2">
                            <template x-for="tag in formData.tags" :key="tag">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-primary/10 text-primary" x-text="tag"></span>
                            </template>
                        </div>
                    </div>

                    <div class="flex gap-3 pt-4">
                        <button type="submit" class="flex-1 py-3 px-4 bg-primary text-white font-medium rounded-xl hover:bg-primary/90 transition-colors flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined text-[18px]" x-text="editingTask ? 'save' : 'add_task'"></span>
                            <span x-text="editingTask ? 'Update Task' : 'Create Task'"></span>
                        </button>
                        <button x-show="editingTask" @click="deleteTask()" type="button" class="py-3 px-4 bg-red-500/10 text-red-500 font-medium rounded-xl hover:bg-red-500/20 transition-colors">
                            <span class="material-symbols-outlined">delete</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        window.initialTasks = @json($tasksByStatus);

        function taskboard() {
            return {
                activeTab: 'kanban',
                tasks: [],
                showModal: false,
                editingTask: null,
                formData: {},
                tagsInput: '',
                _sortables: [],
                _dragging: false,
                // Activity Log state
                activityLogs: [],
                activityGroups: [],
                activityLoading: false,
                activityDateFilter: '',
                activityTypeFilter: '',
                activityMeta: { current_page: 1, last_page: 1, total: 0 },
                // Schedule state
                scheduledRoutines: [],
                scheduleLoading: false,
                scheduleCategoryFilter: '',

                init() {
                    const data = window.initialTasks;
                    this.tasks = [
                        ...(data.backlog || []),
                        ...(data.todo || []),
                        ...(data.in_progress || []),
                        ...(data.done || [])
                    ];
                    this.renderAllColumns();
                    this.$nextTick(() => this.initializeSortable());
                },

                getColumnTasks(status) {
                    return this.tasks.filter(t => t.status === status).sort((a, b) => (a.position || 0) - (b.position || 0));
                },

                renderAllColumns() {
                    ['backlog', 'todo', 'in_progress', 'done'].forEach(status => this.renderColumn(status));
                },

                renderColumn(status) {
                    const col = this.$refs['col_' + status];
                    if (!col) return;
                    col.innerHTML = '';
                    this.getColumnTasks(status).forEach(task => col.appendChild(this.createCardElement(task)));
                },

                createCardElement(task) {
                    const div = document.createElement('div');
                    div.className = 'task-card group relative flex flex-col rounded-xl shadow-sm bg-slate-50 dark:bg-[#233f48] border border-slate-200 dark:border-slate-700/50 p-4 transition-all hover:shadow-md hover:-translate-y-0.5 active:scale-[0.98]';
                    div.setAttribute('data-task-id', task.id);
                    
                    const priorityConfig = { 
                        high: { dot: 'bg-red-500', text: 'Critical' }, 
                        medium: { dot: 'bg-yellow-400', text: 'Medium' }, 
                        low: { dot: 'bg-emerald-500', text: 'Low' } 
                    }[task.priority] || { dot: 'bg-slate-400', text: 'None' };
                    
                    const assignee = { 
                        sandi: { avatar: 'S', color: 'bg-blue-500' }, 
                        alex: { avatar: 'A', color: 'bg-primary' } 
                    }[task.assigned_to];
                    
                    const tags = (task.tags || []).slice(0, 2).map(tag => 
                        `<span class="inline-flex h-6 items-center px-3 rounded-full bg-slate-200 dark:bg-slate-700 text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">#${this.escapeHtml(tag)}</span>`
                    ).join('');
                    
                    const dueDate = task.due_date ? new Date(task.due_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) : '';
                    const description = task.description ? (typeof marked !== 'undefined' ? marked.parse(task.description) : task.description.replace(/\n/g, '<br>')) : '';

                    div.innerHTML = `
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex gap-2">${tags}</div>
                            <div class="flex items-center gap-1.5">
                                <div class="size-2 rounded-full ${priorityConfig.dot}"></div>
                                <span class="text-slate-500 dark:text-slate-400 text-[11px] font-medium">${priorityConfig.text}</span>
                            </div>
                        </div>
                        <h4 class="text-slate-900 dark:text-white text-base font-bold leading-tight mb-1 line-clamp-2">${this.escapeHtml(task.title)}</h4>
                        ${description ? `<div class="text-slate-500 dark:text-slate-400 text-sm leading-relaxed mb-4 line-clamp-2 prose prose-sm max-w-none">${description}</div>` : '<div class="mb-4"></div>'}
                        <div class="flex items-center justify-between border-t border-slate-200 dark:border-slate-700/50 pt-3 mt-auto">
                            <div class="flex items-center gap-2">
                                ${assignee ? `<div class="size-7 rounded-full ${assignee.color} flex items-center justify-center text-white text-xs font-bold border-2 border-white dark:border-slate-700">${assignee.avatar}</div>` : ''}
                                ${dueDate ? `<div class="flex items-center text-slate-500 dark:text-slate-400 text-xs"><span class="material-symbols-outlined text-[14px] mr-1">calendar_today</span><span>${dueDate}</span></div>` : ''}
                            </div>
                            <div class="opacity-0 group-hover:opacity-100 transition-opacity">
                                <span class="material-symbols-outlined text-[18px] text-slate-400">more_horiz</span>
                            </div>
                        </div>
                    `;

                    div.addEventListener('click', () => {
                        if (!this._dragging) {
                            const t = this.tasks.find(x => x.id === task.id);
                            if (t) this.openEditTask(t);
                        }
                    });

                    return div;
                },

                escapeHtml(text) {
                    const d = document.createElement('div');
                    d.textContent = text;
                    return d.innerHTML;
                },

                initializeSortable() {
                    const self = this;
                    document.querySelectorAll('.sortable-column').forEach(column => {
                        const sortable = new Sortable(column, {
                            group: 'kanban',
                            animation: 150,
                            forceFallback: true,
                            fallbackOnBody: true,
                            fallbackTolerance: 3,
                            ghostClass: 'sortable-ghost',
                            chosenClass: 'sortable-chosen',
                            dragClass: 'sortable-drag',
                            onStart() { self._dragging = true; },
                            onEnd(evt) {
                                const taskId = parseInt(evt.item.getAttribute('data-task-id'));
                                const task = self.tasks.find(t => t.id === taskId);
                                if (task) task.status = evt.to.dataset.column;

                                ['backlog', 'todo', 'in_progress', 'done'].forEach(status => {
                                    const col = document.querySelector(`[data-column="${status}"]`);
                                    if (!col) return;
                                    col.querySelectorAll('.task-card').forEach((card, index) => {
                                        const id = parseInt(card.getAttribute('data-task-id'));
                                        const t = self.tasks.find(x => x.id === id);
                                        if (t) { t.status = status; t.position = index; }
                                    });
                                });

                                self.saveTaskPositions();
                                setTimeout(() => { self._dragging = false; }, 100);
                            }
                        });
                        self._sortables.push(sortable);
                    });
                },

                saveTaskPositions() {
                    fetch('/tasks/positions', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ tasks: this.tasks.map(t => ({ id: t.id, status: t.status, position: t.position || 0 })) })
                    });
                },

                openAddTask(status) {
                    this.editingTask = null;
                    this.formData = { title: '', description: '', status: status, priority: 'medium', assigned_to: '', due_date: '', tags: [] };
                    this.tagsInput = '';
                    this.showModal = true;
                },

                openEditTask(task) {
                    this.editingTask = task;
                    this.formData = { ...task };
                    this.tagsInput = task.tags ? task.tags.join(', ') : '';
                    this.showModal = true;
                },

                closeModal() {
                    this.showModal = false;
                    this.editingTask = null;
                    this.formData = {};
                },

                updateTags() {
                    this.formData.tags = this.tagsInput.split(',').map(tag => tag.trim()).filter(tag => tag.length > 0);
                },

                saveTask() {
                    const url = this.editingTask ? `/tasks/${this.editingTask.id}` : '/tasks';
                    fetch(url, {
                        method: this.editingTask ? 'PUT' : 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(this.formData)
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            if (this.editingTask) {
                                const idx = this.tasks.findIndex(t => t.id === this.editingTask.id);
                                if (idx !== -1) this.tasks[idx] = data.task;
                            } else {
                                this.tasks.push(data.task);
                            }
                            this.renderAllColumns();
                            this.closeModal();
                        }
                    });
                },

                deleteTask() {
                    if (!confirm('Delete this task?')) return;
                    fetch(`/tasks/${this.editingTask.id}`, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            this.tasks = this.tasks.filter(t => t.id !== this.editingTask.id);
                            this.renderAllColumns();
                            this.closeModal();
                        }
                    });
                },

                // ─── Activity Log Methods ───

                fetchActivityLogs(page = 1) {
                    this.activityLoading = true;
                    if (page === 1) this.activityLogs = [];

                    let url = `/api/activity-logs?page=${page}`;
                    if (this.activityDateFilter) url += `&date=${this.activityDateFilter}`;
                    if (this.activityTypeFilter) url += `&type=${this.activityTypeFilter}`;

                    fetch(url, {
                        headers: { 'Authorization': 'Bearer {{ config("app.api_token", "podklanec") }}' }
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            if (page === 1) {
                                this.activityLogs = data.data;
                            } else {
                                this.activityLogs = [...this.activityLogs, ...data.data];
                            }
                            this.activityMeta = data.meta;
                            this.groupActivityLogs();
                        }
                        this.activityLoading = false;
                    })
                    .catch(() => { this.activityLoading = false; });
                },

                loadMoreActivity() {
                    if (this.activityMeta.current_page < this.activityMeta.last_page) {
                        this.fetchActivityLogs(this.activityMeta.current_page + 1);
                    }
                },

                groupActivityLogs() {
                    const groups = {};
                    this.activityLogs.forEach(log => {
                        const date = log.created_at.substring(0, 10);
                        if (!groups[date]) groups[date] = [];
                        groups[date].push(log);
                    });
                    const today = new Date().toISOString().substring(0, 10);
                    const yesterday = new Date(Date.now() - 86400000).toISOString().substring(0, 10);
                    this.activityGroups = Object.keys(groups).sort().reverse().map(date => ({
                        date,
                        dateLabel: date === today ? 'Today' : date === yesterday ? 'Yesterday' : new Date(date + 'T00:00:00').toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' }),
                        items: groups[date]
                    }));
                },

                getActivityIcon(type) {
                    return { email: '📧', sms: '📱', order_fix: '🛒', analysis: '📊', integration: '🔌', other: '📝' }[type] || '📝';
                },

                getActivityDotColor(type) {
                    return {
                        email: 'bg-blue-500',
                        sms: 'bg-green-500',
                        order_fix: 'bg-orange-500',
                        analysis: 'bg-purple-500',
                        integration: 'bg-cyan-500',
                        other: 'bg-slate-400'
                    }[type] || 'bg-slate-400';
                },

                getActivityBadgeClass(type) {
                    return {
                        email: 'bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-400',
                        sms: 'bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-400',
                        order_fix: 'bg-orange-100 text-orange-700 dark:bg-orange-500/20 dark:text-orange-400',
                        analysis: 'bg-purple-100 text-purple-700 dark:bg-purple-500/20 dark:text-purple-400',
                        integration: 'bg-cyan-100 text-cyan-700 dark:bg-cyan-500/20 dark:text-cyan-400',
                        other: 'bg-slate-100 text-slate-600 dark:bg-slate-500/20 dark:text-slate-400'
                    }[type] || 'bg-slate-100 text-slate-600 dark:bg-slate-500/20 dark:text-slate-400';
                },

                formatActivityTime(datetime) {
                    return new Date(datetime).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: false });
                },

                // ─── Schedule Methods ───

                fetchScheduledRoutines() {
                    this.scheduleLoading = true;
                    let url = '/api/scheduled-routines';
                    if (this.scheduleCategoryFilter) url += `?category=${this.scheduleCategoryFilter}`;

                    fetch(url, {
                        headers: { 'Authorization': 'Bearer {{ config("app.api_token", "podklanec") }}' }
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            this.scheduledRoutines = data.data;
                        }
                        this.scheduleLoading = false;
                    })
                    .catch(() => { this.scheduleLoading = false; });
                },

                toggleRoutine(routine) {
                    const newEnabled = !routine.enabled;
                    fetch(`/api/scheduled-routines/${routine.id}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': 'Bearer {{ config("app.api_token", "podklanec") }}'
                        },
                        body: JSON.stringify({ enabled: newEnabled })
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            routine.enabled = newEnabled;
                        }
                    });
                },

                getScheduleIcon(category) {
                    return { email: '📧', sms: '📱', orders: '🛒', analysis: '📊', monitoring: '👁', other: '📝' }[category] || '📝';
                },

                getFrequencyBadge(routine) {
                    if (routine.frequency) return routine.frequency;
                    if (routine.schedule_type === 'daily') return 'Daily ' + routine.schedule_time;
                    if (routine.schedule_type === 'hourly') return 'Hourly ' + routine.schedule_time;
                    return routine.schedule_type;
                },

                getScheduleBadgeClass(category) {
                    return {
                        email: 'bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-400',
                        sms: 'bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-400',
                        orders: 'bg-orange-100 text-orange-700 dark:bg-orange-500/20 dark:text-orange-400',
                        analysis: 'bg-purple-100 text-purple-700 dark:bg-purple-500/20 dark:text-purple-400',
                        monitoring: 'bg-cyan-100 text-cyan-700 dark:bg-cyan-500/20 dark:text-cyan-400',
                        other: 'bg-slate-100 text-slate-600 dark:bg-slate-500/20 dark:text-slate-400'
                    }[category] || 'bg-slate-100 text-slate-600 dark:bg-slate-500/20 dark:text-slate-400';
                }
            }
        }
    </script>
</body>
</html>
