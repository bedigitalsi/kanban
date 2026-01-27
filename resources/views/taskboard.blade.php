<!DOCTYPE html>
<html lang="en" x-data="{ darkMode: localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches) }" x-bind:class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Taskboard - Kanban</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 dark:bg-gray-900 transition-colors duration-300" x-data="taskboard()">
    <!-- Header -->
    <header class="bg-white dark:bg-gray-800 shadow border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-6">
                <div class="flex items-center">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Taskboard</h1>
                </div>
                
                <div class="flex items-center space-x-4">
                    <button @click="darkMode = !darkMode; localStorage.setItem('theme', darkMode ? 'dark' : 'light')" 
                            class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                        <span x-show="!darkMode">🌙</span>
                        <span x-show="darkMode">☀️</span>
                    </button>

                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <!-- Main content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">

            <!-- Backlog Column -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        📝 Backlog
                        <span class="ml-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm px-2 py-1 rounded-full" x-text="getColumnTasks('backlog').length"></span>
                    </h2>
                    <button @click="openAddTask('backlog')" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 text-sm font-medium">+ Add Task</button>
                </div>
                <div class="p-4 space-y-3 min-h-[300px] sortable-column" data-column="backlog" x-ref="col_backlog"></div>
            </div>

            <!-- To Do Column -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        🔵 To Do
                        <span class="ml-2 bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 text-sm px-2 py-1 rounded-full" x-text="getColumnTasks('todo').length"></span>
                    </h2>
                    <button @click="openAddTask('todo')" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 text-sm font-medium">+ Add Task</button>
                </div>
                <div class="p-4 space-y-3 min-h-[300px] sortable-column" data-column="todo" x-ref="col_todo"></div>
            </div>

            <!-- In Progress Column -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        🟡 In Progress
                        <span class="ml-2 bg-yellow-100 dark:bg-yellow-900 text-yellow-700 dark:text-yellow-300 text-sm px-2 py-1 rounded-full" x-text="getColumnTasks('in_progress').length"></span>
                    </h2>
                    <button @click="openAddTask('in_progress')" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 text-sm font-medium">+ Add Task</button>
                </div>
                <div class="p-4 space-y-3 min-h-[300px] sortable-column" data-column="in_progress" x-ref="col_in_progress"></div>
            </div>

            <!-- Done Column -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        ✅ Done
                        <span class="ml-2 bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300 text-sm px-2 py-1 rounded-full" x-text="getColumnTasks('done').length"></span>
                    </h2>
                    <button @click="openAddTask('done')" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 text-sm font-medium">+ Add Task</button>
                </div>
                <div class="p-4 space-y-3 min-h-[300px] sortable-column" data-column="done" x-ref="col_done"></div>
            </div>

        </div>
    </main>

    <!-- Task Modal -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 transition-opacity">
                <div class="absolute inset-0 bg-gray-500 dark:bg-gray-900 opacity-75" @click="closeModal()"></div>
            </div>

            <div x-show="showModal" x-transition class="relative z-10 inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <div>
                    <div class="mt-3 text-center sm:mt-0 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" x-text="editingTask ? 'Edit Task' : 'Add New Task'"></h3>
                        
                        <form @submit.prevent="saveTask()" class="mt-6 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
                                <input type="text" x-model="formData.title" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 text-gray-900 dark:text-white bg-white dark:bg-gray-700 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                                <textarea x-model="formData.description" rows="3" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 text-gray-900 dark:text-white bg-white dark:bg-gray-700 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Supports markdown..."></textarea>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Priority</label>
                                    <select x-model="formData.priority" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 text-gray-900 dark:text-white bg-white dark:bg-gray-700 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        <option value="low">🟢 Low</option>
                                        <option value="medium">🟡 Medium</option>
                                        <option value="high">🔴 High</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Assigned To</label>
                                    <select x-model="formData.assigned_to" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 text-gray-900 dark:text-white bg-white dark:bg-gray-700 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        <option value="">Unassigned</option>
                                        <option value="sandi">👩‍💼 Sandi</option>
                                        <option value="alex">🤖 Alex</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Due Date</label>
                                <input type="date" x-model="formData.due_date" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 text-gray-900 dark:text-white bg-white dark:bg-gray-700 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tags</label>
                                <input type="text" x-model="tagsInput" @input="updateTags()" placeholder="Enter tags separated by commas" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 text-gray-900 dark:text-white bg-white dark:bg-gray-700 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <div x-show="formData.tags && formData.tags.length > 0" class="mt-2 flex flex-wrap gap-2">
                                    <template x-for="tag in formData.tags" :key="tag">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200" x-text="tag"></span>
                                    </template>
                                </div>
                            </div>

                            <div class="mt-5 sm:mt-6 sm:flex sm:flex-row-reverse">
                                <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                                    <span x-text="editingTask ? 'Update Task' : 'Create Task'"></span>
                                </button>
                                
                                <button x-show="editingTask" @click="deleteTask()" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                    Delete
                                </button>
                                
                                <button @click="closeModal()" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initial data from server
        window.initialTasks = @json($tasksByStatus);

        function taskboard() {
            return {
                tasks: [],
                showModal: false,
                editingTask: null,
                formData: {},
                tagsInput: '',
                _sortables: [],

                init() {
                    // Flatten all tasks into a single array
                    const data = window.initialTasks;
                    this.tasks = [
                        ...(data.backlog || []),
                        ...(data.todo || []),
                        ...(data.in_progress || []),
                        ...(data.done || [])
                    ];

                    // Render cards into columns
                    this.renderAllColumns();

                    // Init sortable after DOM is ready
                    this.$nextTick(() => {
                        this.initializeSortable();
                    });
                },

                getColumnTasks(status) {
                    return this.tasks.filter(t => t.status === status).sort((a, b) => (a.position || 0) - (b.position || 0));
                },

                renderAllColumns() {
                    ['backlog', 'todo', 'in_progress', 'done'].forEach(status => {
                        this.renderColumn(status);
                    });
                },

                renderColumn(status) {
                    const col = this.$refs['col_' + status];
                    if (!col) return;
                    col.innerHTML = '';
                    const tasks = this.getColumnTasks(status);
                    tasks.forEach(task => {
                        col.appendChild(this.createCardElement(task));
                    });
                },

                createCardElement(task) {
                    const div = document.createElement('div');
                    div.className = 'task-card bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow duration-200 cursor-pointer';
                    div.setAttribute('data-task-id', task.id);
                    
                    const priorityEmoji = { high: '🔴', medium: '🟡', low: '🟢' }[task.priority] || '⚪';
                    const assignee = { sandi: { avatar: 'S', color: 'bg-blue-500' }, alex: { avatar: 'A', color: 'bg-green-500' } }[task.assigned_to];
                    const tags = (task.tags || []).map(tag => `<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-600 text-gray-800 dark:text-gray-200">${this.escapeHtml(tag)}</span>`).join(' ');
                    const dueDate = task.due_date ? new Date(task.due_date).toLocaleDateString() : '';
                    const description = task.description ? (typeof marked !== 'undefined' ? marked.parse(task.description) : task.description.replace(/\n/g, '<br>')) : '';

                    div.innerHTML = `
                        <div class="flex items-start justify-between mb-2">
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white line-clamp-2">${this.escapeHtml(task.title)}</h3>
                            <span class="ml-2 text-lg flex-shrink-0">${priorityEmoji}</span>
                        </div>
                        ${description ? `<div class="text-xs text-gray-600 dark:text-gray-300 mb-2 line-clamp-3 prose prose-sm max-w-none">${description}</div>` : ''}
                        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                            <div class="flex items-center space-x-2">
                                ${assignee ? `<span class="w-5 h-5 rounded-full ${assignee.color} text-white text-xs flex items-center justify-center">${assignee.avatar}</span>` : ''}
                                ${dueDate ? `<span>📅 ${dueDate}</span>` : ''}
                            </div>
                            <span>${new Date(task.created_at).toLocaleDateString()}</span>
                        </div>
                        ${tags ? `<div class="mt-2 flex flex-wrap gap-1">${tags}</div>` : ''}
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
                            ghostClass: 'opacity-50',
                            chosenClass: 'ring-2 ring-indigo-500',
                            dragClass: 'rotate-2',
                            onStart() {
                                self._dragging = true;
                            },
                            onEnd(evt) {
                                const taskId = parseInt(evt.item.getAttribute('data-task-id'));
                                const newStatus = evt.to.dataset.column;
                                const oldStatus = evt.from.dataset.column;

                                // Update task data
                                const task = self.tasks.find(t => t.id === taskId);
                                if (task) {
                                    task.status = newStatus;
                                }

                                // Rebuild positions from current DOM order
                                ['backlog', 'todo', 'in_progress', 'done'].forEach(status => {
                                    const col = document.querySelector(`[data-column="${status}"]`);
                                    if (!col) return;
                                    const cards = col.querySelectorAll('.task-card');
                                    cards.forEach((card, index) => {
                                        const id = parseInt(card.getAttribute('data-task-id'));
                                        const t = self.tasks.find(x => x.id === id);
                                        if (t) {
                                            t.status = status;
                                            t.position = index;
                                        }
                                    });
                                });

                                // Save to server
                                self.saveTaskPositions();

                                setTimeout(() => { self._dragging = false; }, 100);
                            }
                        });
                        self._sortables.push(sortable);
                    });
                },

                _dragging: false,

                saveTaskPositions() {
                    const positions = this.tasks.map(t => ({
                        id: t.id,
                        status: t.status,
                        position: t.position || 0
                    }));

                    fetch('/tasks/positions', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ tasks: positions })
                    });
                },

                openAddTask(status) {
                    this.editingTask = null;
                    this.formData = {
                        title: '',
                        description: '',
                        status: status,
                        priority: 'medium',
                        assigned_to: '',
                        due_date: '',
                        tags: []
                    };
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
                    this.formData.tags = this.tagsInput
                        .split(',')
                        .map(tag => tag.trim())
                        .filter(tag => tag.length > 0);
                },

                saveTask() {
                    const url = this.editingTask 
                        ? `/tasks/${this.editingTask.id}`
                        : '/tasks';
                    
                    const method = this.editingTask ? 'PUT' : 'POST';

                    fetch(url, {
                        method,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(this.formData)
                    })
                    .then(response => response.json())
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
                    if (!confirm('Are you sure you want to delete this task?')) return;
                    
                    fetch(`/tasks/${this.editingTask.id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.tasks = this.tasks.filter(t => t.id !== this.editingTask.id);
                            this.renderAllColumns();
                            this.closeModal();
                        }
                    });
                }
            }
        }
    </script>
    
    <style>
        [x-cloak] { display: none !important; }
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .task-card:hover {
            transform: translateY(-1px);
        }
        .sortable-ghost {
            opacity: 0.5;
        }
        .rotate-2 {
            transform: rotate(2deg);
        }
    </style>
</body>
</html>
