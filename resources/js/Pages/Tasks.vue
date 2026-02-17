<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Sortable from 'sortablejs';

// --- State ---
const tasks = ref([]);
const loading = ref(true);
const filterAssignee = ref('');
const filterPriority = ref('');
const filterSearch = ref('');
const selectedTask = ref(null);
const showDetailPanel = ref(false);
const showCreateModal = ref(false);
const createForStatus = ref('todo');
const mobileActiveColumn = ref('todo');
const sortableInstances = ref([]);

const columns = [
  { key: 'backlog', label: 'Backlog', icon: '📋' },
  { key: 'todo', label: 'Todo', icon: '📌' },
  { key: 'in_progress', label: 'In Progress', icon: '🔧' },
  { key: 'done', label: 'Done', icon: '✅' },
];

const priorities = ['high', 'medium', 'low'];
const assignees = ['alex', 'sandi'];

const defaultForm = {
  title: '',
  description: '',
  status: 'todo',
  priority: 'medium',
  assigned_to: '',
  tags: '',
  board: 'tasks',
  project: '',
  due_date: '',
};
const form = ref({ ...defaultForm });
const editForm = ref({});
const saving = ref(false);

// --- API helpers ---
function getCookie(name) {
  const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
  return match ? decodeURIComponent(match[2]) : '';
}

async function api(method, url, body = null) {
  const headers = {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'X-XSRF-TOKEN': getCookie('XSRF-TOKEN'),
  };
  const opts = { method, headers, credentials: 'same-origin' };
  if (body) opts.body = JSON.stringify(body);
  const res = await fetch(url, opts);
  if (!res.ok) throw new Error(`API ${res.status}`);
  return res.json();
}

// --- Data ---
async function fetchTasks() {
  try {
    const res = await api('GET', '/api/tasks?board=tasks');
    tasks.value = (res.data || []).sort((a, b) => (a.position ?? 999) - (b.position ?? 999));
  } catch (e) {
    console.error('Failed to fetch tasks:', e);
  } finally {
    loading.value = false;
  }
}

const filteredTasks = computed(() => {
  return tasks.value.filter(t => {
    if (filterAssignee.value && t.assigned_to !== filterAssignee.value) return false;
    if (filterPriority.value && t.priority !== filterPriority.value) return false;
    if (filterSearch.value) {
      const q = filterSearch.value.toLowerCase();
      const haystack = `${t.title} ${t.description || ''} ${(t.tags || []).join(' ')}`.toLowerCase();
      if (!haystack.includes(q)) return false;
    }
    return true;
  });
});

function tasksForColumn(key) {
  return filteredTasks.value.filter(t => t.status === key);
}

// --- Priority / Assignee helpers ---
function priorityColor(p) {
  return p === 'high' ? 'bg-red-500' : p === 'medium' ? 'bg-yellow-400' : 'bg-green-500';
}
function priorityLabel(p) {
  return p === 'high' ? 'High' : p === 'medium' ? 'Medium' : 'Low';
}
function assigneeInitial(a) {
  if (!a) return '?';
  return a.charAt(0).toUpperCase();
}
function assigneeBg(a) {
  return a === 'alex' ? 'bg-blue-500' : a === 'sandi' ? 'bg-purple-500' : 'bg-gray-400';
}
function formatDate(d) {
  if (!d) return '';
  const date = new Date(d);
  return date.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });
}
function isOverdue(d) {
  if (!d) return false;
  return new Date(d) < new Date(new Date().toDateString());
}
function parseTags(t) {
  if (Array.isArray(t)) return t;
  if (typeof t === 'string' && t) return t.split(',').map(s => s.trim()).filter(Boolean);
  return [];
}

// --- CRUD ---
async function createTask() {
  saving.value = true;
  try {
    const payload = { ...form.value };
    payload.tags = payload.tags ? payload.tags.split(',').map(s => s.trim()).filter(Boolean) : [];
    if (!payload.due_date) delete payload.due_date;
    if (!payload.assigned_to) delete payload.assigned_to;
    if (!payload.project) delete payload.project;
    const res = await api('POST', '/api/tasks', payload);
    if (res.data) tasks.value.push(res.data);
    else await fetchTasks();
    closeCreateModal();
  } catch (e) {
    console.error('Create failed:', e);
    alert('Failed to create task');
  } finally {
    saving.value = false;
  }
}

async function updateTask() {
  saving.value = true;
  try {
    const payload = { ...editForm.value };
    payload.tags = typeof payload.tags === 'string'
      ? payload.tags.split(',').map(s => s.trim()).filter(Boolean)
      : payload.tags || [];
    if (!payload.due_date) delete payload.due_date;
    const res = await api('PUT', `/api/tasks/${payload.id}`, payload);
    const idx = tasks.value.findIndex(t => t.id === payload.id);
    if (idx !== -1) tasks.value[idx] = res.data || { ...tasks.value[idx], ...payload };
    selectedTask.value = tasks.value[idx] || selectedTask.value;
  } catch (e) {
    console.error('Update failed:', e);
    alert('Failed to update task');
  } finally {
    saving.value = false;
  }
}

async function deleteTask(id) {
  if (!confirm('Delete this task?')) return;
  try {
    await api('DELETE', `/api/tasks/${id}`);
    tasks.value = tasks.value.filter(t => t.id !== id);
    closeDetailPanel();
  } catch (e) {
    console.error('Delete failed:', e);
  }
}

async function savePositions(items, status) {
  const positions = items.map((id, i) => ({ id: parseInt(id), position: i, status }));
  try {
    await api('POST', '/api/tasks/positions', { positions });
  } catch (e) {
    console.error('Position save failed:', e);
  }
}

// --- Drag & Drop ---
function initSortable() {
  destroySortable();
  nextTick(() => {
    columns.forEach(col => {
      const el = document.getElementById(`col-${col.key}`);
      if (!el) return;
      const s = Sortable.create(el, {
        group: 'kanban',
        animation: 200,
        ghostClass: 'opacity-30',
        dragClass: 'rotate-2',
        handle: '.task-card',
        draggable: '.task-card',
        onEnd(evt) {
          const taskId = parseInt(evt.item.dataset.id);
          const newStatus = evt.to.dataset.status;
          const task = tasks.value.find(t => t.id === taskId);
          if (task) task.status = newStatus;
          // Collect new order
          const ids = Array.from(evt.to.children)
            .filter(c => c.dataset.id)
            .map(c => c.dataset.id);
          savePositions(ids, newStatus);
          // Also update source column if different
          if (evt.from !== evt.to) {
            const fromStatus = evt.from.dataset.status;
            const fromIds = Array.from(evt.from.children)
              .filter(c => c.dataset.id)
              .map(c => c.dataset.id);
            savePositions(fromIds, fromStatus);
          }
        }
      });
      sortableInstances.value.push(s);
    });
  });
}

function destroySortable() {
  sortableInstances.value.forEach(s => s.destroy());
  sortableInstances.value = [];
}

// --- UI ---
function openDetailPanel(task) {
  selectedTask.value = task;
  editForm.value = {
    ...task,
    tags: Array.isArray(task.tags) ? task.tags.join(', ') : (task.tags || ''),
  };
  showDetailPanel.value = true;
}
function closeDetailPanel() {
  showDetailPanel.value = false;
  selectedTask.value = null;
}
function openCreateModal(status = 'todo') {
  form.value = { ...defaultForm, status };
  createForStatus.value = status;
  showCreateModal.value = true;
}
function closeCreateModal() {
  showCreateModal.value = false;
  form.value = { ...defaultForm };
}

// Keyboard
function handleKeydown(e) {
  if (e.key === 'Escape') {
    if (showDetailPanel.value) closeDetailPanel();
    if (showCreateModal.value) closeCreateModal();
  }
}

// --- Lifecycle ---
onMounted(() => {
  fetchTasks().then(() => initSortable());
  document.addEventListener('keydown', handleKeydown);
});
onBeforeUnmount(() => {
  destroySortable();
  document.removeEventListener('keydown', handleKeydown);
});

// Re-init sortable when filters change (DOM changes)
watch([filterAssignee, filterPriority, filterSearch], () => {
  nextTick(() => initSortable());
});
</script>

<template>
  <AppLayout>
    <div class="h-full flex flex-col min-h-0">
      <!-- Header -->
      <div class="flex-none px-4 sm:px-6 pt-4 pb-3">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Tasks</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Manage your kanban board</p>
          </div>
          <button
            @click="openCreateModal()"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium text-white shadow-sm transition-all hover:shadow-md active:scale-95"
            style="background-color: #13b6ec;"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New Task
          </button>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap items-center gap-2 mt-3">
          <div class="relative">
            <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input
              v-model="filterSearch"
              type="text"
              placeholder="Search tasks…"
              class="pl-8 pr-3 py-1.5 text-sm rounded-lg border border-gray-200 dark:border-[#233f48] bg-white dark:bg-[#192d33] text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#13b6ec]/40 w-48"
            />
          </div>
          <select
            v-model="filterAssignee"
            class="text-sm rounded-lg border border-gray-200 dark:border-[#233f48] bg-white dark:bg-[#192d33] text-gray-700 dark:text-gray-200 px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-[#13b6ec]/40"
          >
            <option value="">All assignees</option>
            <option value="alex">Alex</option>
            <option value="sandi">Sandi</option>
          </select>
          <select
            v-model="filterPriority"
            class="text-sm rounded-lg border border-gray-200 dark:border-[#233f48] bg-white dark:bg-[#192d33] text-gray-700 dark:text-gray-200 px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-[#13b6ec]/40"
          >
            <option value="">All priorities</option>
            <option value="high">High</option>
            <option value="medium">Medium</option>
            <option value="low">Low</option>
          </select>
          <button
            v-if="filterAssignee || filterPriority || filterSearch"
            @click="filterAssignee = ''; filterPriority = ''; filterSearch = ''"
            class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 underline"
          >Clear filters</button>
        </div>

        <!-- Mobile column tabs -->
        <div class="flex sm:hidden gap-1 mt-3 overflow-x-auto pb-1">
          <button
            v-for="col in columns"
            :key="col.key"
            @click="mobileActiveColumn = col.key"
            :class="[
              'px-3 py-1.5 text-xs font-medium rounded-lg whitespace-nowrap transition-colors',
              mobileActiveColumn === col.key
                ? 'bg-[#13b6ec] text-white'
                : 'bg-gray-100 dark:bg-[#192d33] text-gray-600 dark:text-gray-300'
            ]"
          >
            {{ col.icon }} {{ col.label }}
            <span class="ml-1 opacity-70">({{ tasksForColumn(col.key).length }})</span>
          </button>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="flex-1 flex items-center justify-center">
        <div class="flex items-center gap-3 text-gray-400">
          <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
          Loading tasks…
        </div>
      </div>

      <!-- Board -->
      <div v-else class="flex-1 min-h-0 px-4 sm:px-6 pb-4 overflow-x-auto">
        <div class="flex gap-4 h-full min-w-max sm:min-w-0">
          <div
            v-for="col in columns"
            :key="col.key"
            :class="[
              'flex flex-col w-72 sm:w-auto sm:flex-1 min-w-[272px] rounded-xl',
              'bg-gray-50 dark:bg-[#11232a] border border-gray-200/60 dark:border-[#233f48]/60',
              // Mobile: show only active column
              'sm:!flex',
              mobileActiveColumn === col.key ? 'flex' : 'hidden'
            ]"
          >
            <!-- Column header -->
            <div class="flex items-center justify-between px-3 py-2.5 border-b border-gray-200/60 dark:border-[#233f48]/60">
              <div class="flex items-center gap-2">
                <span class="text-base">{{ col.icon }}</span>
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ col.label }}</span>
                <span class="text-xs font-medium px-1.5 py-0.5 rounded-full bg-gray-200/80 dark:bg-[#233f48] text-gray-600 dark:text-gray-300">
                  {{ tasksForColumn(col.key).length }}
                </span>
              </div>
              <button
                @click="openCreateModal(col.key)"
                class="p-1 rounded-md text-gray-400 hover:text-[#13b6ec] hover:bg-gray-200/50 dark:hover:bg-[#233f48] transition-colors"
                title="Add task"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
              </button>
            </div>

            <!-- Cards container (sortable) -->
            <div
              :id="`col-${col.key}`"
              :data-status="col.key"
              class="flex-1 overflow-y-auto p-2 space-y-2 min-h-[60px]"
            >
              <div
                v-for="task in tasksForColumn(col.key)"
                :key="task.id"
                :data-id="task.id"
                @click="openDetailPanel(task)"
                class="task-card group cursor-pointer rounded-xl p-3 border transition-all duration-200
                  bg-white dark:bg-[#192d33] border-gray-200/80 dark:border-[#233f48]
                  shadow-sm hover:shadow-md hover:-translate-y-0.5
                  active:scale-[0.98]"
              >
                <!-- Priority + Title -->
                <div class="flex items-start gap-2">
                  <span
                    :class="['w-2 h-2 rounded-full mt-1.5 flex-none', priorityColor(task.priority)]"
                    :title="priorityLabel(task.priority)"
                  />
                  <span class="text-sm font-medium text-gray-800 dark:text-gray-100 leading-snug line-clamp-2">
                    {{ task.title }}
                  </span>
                </div>

                <!-- Tags -->
                <div v-if="parseTags(task.tags).length" class="flex flex-wrap gap-1 mt-2">
                  <span
                    v-for="tag in parseTags(task.tags)"
                    :key="tag"
                    class="text-[10px] font-medium px-1.5 py-0.5 rounded-md bg-[#13b6ec]/10 text-[#13b6ec] dark:bg-[#13b6ec]/20"
                  >{{ tag }}</span>
                </div>

                <!-- Footer: due date + assignee -->
                <div class="flex items-center justify-between mt-2.5">
                  <span
                    v-if="task.due_date"
                    :class="[
                      'text-[11px] font-medium',
                      isOverdue(task.due_date) && task.status !== 'done'
                        ? 'text-red-500'
                        : 'text-gray-400 dark:text-gray-500'
                    ]"
                  >
                    📅 {{ formatDate(task.due_date) }}
                  </span>
                  <span v-else />
                  <span
                    v-if="task.assigned_to"
                    :class="[
                      'w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold text-white',
                      assigneeBg(task.assigned_to)
                    ]"
                    :title="task.assigned_to"
                  >{{ assigneeInitial(task.assigned_to) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Detail / Edit Slide-out Panel -->
    <Teleport to="body">
      <Transition name="panel">
        <div v-if="showDetailPanel && selectedTask" class="fixed inset-0 z-50 flex justify-end" @click.self="closeDetailPanel">
          <!-- Overlay -->
          <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" @click="closeDetailPanel" />
          <!-- Panel -->
          <div class="relative w-full max-w-lg bg-white dark:bg-[#10222a] shadow-2xl overflow-y-auto border-l border-gray-200 dark:border-[#233f48]">
            <!-- Panel header -->
            <div class="sticky top-0 z-10 flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-[#233f48] bg-white/90 dark:bg-[#10222a]/90 backdrop-blur">
              <h2 class="text-lg font-bold text-gray-900 dark:text-white truncate pr-4">Edit Task</h2>
              <div class="flex items-center gap-2">
                <button
                  @click="deleteTask(selectedTask.id)"
                  class="p-1.5 rounded-lg text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                  title="Delete task"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
                <button @click="closeDetailPanel" class="p-1.5 rounded-lg text-gray-400 hover:bg-gray-100 dark:hover:bg-[#233f48] transition-colors">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
              </div>
            </div>

            <!-- Edit form -->
            <div class="p-5 space-y-4">
              <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Title</label>
                <input v-model="editForm.title" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-200 dark:border-[#233f48] bg-white dark:bg-[#192d33] text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#13b6ec]/40" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Description</label>
                <textarea v-model="editForm.description" rows="4" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-200 dark:border-[#233f48] bg-white dark:bg-[#192d33] text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#13b6ec]/40 resize-y" />
              </div>
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Status</label>
                  <select v-model="editForm.status" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-200 dark:border-[#233f48] bg-white dark:bg-[#192d33] text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#13b6ec]/40">
                    <option v-for="c in columns" :key="c.key" :value="c.key">{{ c.label }}</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Priority</label>
                  <select v-model="editForm.priority" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-200 dark:border-[#233f48] bg-white dark:bg-[#192d33] text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#13b6ec]/40">
                    <option v-for="p in priorities" :key="p" :value="p">{{ priorityLabel(p) }}</option>
                  </select>
                </div>
              </div>
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Assignee</label>
                  <select v-model="editForm.assigned_to" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-200 dark:border-[#233f48] bg-white dark:bg-[#192d33] text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#13b6ec]/40">
                    <option value="">Unassigned</option>
                    <option v-for="a in assignees" :key="a" :value="a">{{ a.charAt(0).toUpperCase() + a.slice(1) }}</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Due Date</label>
                  <input v-model="editForm.due_date" type="date" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-200 dark:border-[#233f48] bg-white dark:bg-[#192d33] text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#13b6ec]/40" />
                </div>
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Tags <span class="font-normal">(comma-separated)</span></label>
                <input v-model="editForm.tags" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-200 dark:border-[#233f48] bg-white dark:bg-[#192d33] text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#13b6ec]/40" placeholder="design, api, urgent" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Project</label>
                <input v-model="editForm.project" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-200 dark:border-[#233f48] bg-white dark:bg-[#192d33] text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#13b6ec]/40" placeholder="Optional project name" />
              </div>

              <div class="flex gap-3 pt-2">
                <button
                  @click="updateTask"
                  :disabled="saving"
                  class="flex-1 px-4 py-2.5 rounded-lg text-sm font-medium text-white transition-all hover:shadow-md active:scale-95 disabled:opacity-50"
                  style="background-color: #13b6ec;"
                >
                  {{ saving ? 'Saving…' : 'Save Changes' }}
                </button>
                <button
                  @click="closeDetailPanel"
                  class="px-4 py-2.5 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-[#233f48] hover:bg-gray-200 dark:hover:bg-[#2a4f5a] transition-colors"
                >Cancel</button>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Create Task Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="closeCreateModal">
          <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" @click="closeCreateModal" />
          <div class="relative w-full max-w-md bg-white dark:bg-[#10222a] rounded-2xl shadow-2xl border border-gray-200 dark:border-[#233f48] overflow-hidden">
            <!-- Modal header -->
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-[#233f48]">
              <h2 class="text-lg font-bold text-gray-900 dark:text-white">New Task</h2>
              <button @click="closeCreateModal" class="p-1 rounded-lg text-gray-400 hover:bg-gray-100 dark:hover:bg-[#233f48] transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
            </div>

            <form @submit.prevent="createTask" class="p-5 space-y-4">
              <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Title *</label>
                <input v-model="form.title" required autofocus class="w-full px-3 py-2 text-sm rounded-lg border border-gray-200 dark:border-[#233f48] bg-white dark:bg-[#192d33] text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#13b6ec]/40" placeholder="What needs to be done?" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Description</label>
                <textarea v-model="form.description" rows="3" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-200 dark:border-[#233f48] bg-white dark:bg-[#192d33] text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#13b6ec]/40 resize-y" placeholder="Optional details…" />
              </div>
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Status</label>
                  <select v-model="form.status" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-200 dark:border-[#233f48] bg-white dark:bg-[#192d33] text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#13b6ec]/40">
                    <option v-for="c in columns" :key="c.key" :value="c.key">{{ c.label }}</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Priority</label>
                  <select v-model="form.priority" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-200 dark:border-[#233f48] bg-white dark:bg-[#192d33] text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#13b6ec]/40">
                    <option v-for="p in priorities" :key="p" :value="p">{{ priorityLabel(p) }}</option>
                  </select>
                </div>
              </div>
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Assignee</label>
                  <select v-model="form.assigned_to" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-200 dark:border-[#233f48] bg-white dark:bg-[#192d33] text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#13b6ec]/40">
                    <option value="">Unassigned</option>
                    <option v-for="a in assignees" :key="a" :value="a">{{ a.charAt(0).toUpperCase() + a.slice(1) }}</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Due Date</label>
                  <input v-model="form.due_date" type="date" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-200 dark:border-[#233f48] bg-white dark:bg-[#192d33] text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#13b6ec]/40" />
                </div>
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Tags <span class="font-normal">(comma-separated)</span></label>
                <input v-model="form.tags" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-200 dark:border-[#233f48] bg-white dark:bg-[#192d33] text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#13b6ec]/40" placeholder="design, api, urgent" />
              </div>

              <div class="flex gap-3 pt-2">
                <button
                  type="submit"
                  :disabled="saving || !form.title.trim()"
                  class="flex-1 px-4 py-2.5 rounded-lg text-sm font-medium text-white transition-all hover:shadow-md active:scale-95 disabled:opacity-50"
                  style="background-color: #13b6ec;"
                >
                  {{ saving ? 'Creating…' : 'Create Task' }}
                </button>
                <button
                  type="button"
                  @click="closeCreateModal"
                  class="px-4 py-2.5 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-[#233f48] hover:bg-gray-200 dark:hover:bg-[#2a4f5a] transition-colors"
                >Cancel</button>
              </div>
            </form>
          </div>
        </div>
      </Transition>
    </Teleport>
  </AppLayout>
</template>

<style scoped>
/* Panel slide transition */
.panel-enter-active,
.panel-leave-active {
  transition: all 0.3s ease;
}
.panel-enter-active > div:last-child,
.panel-leave-active > div:last-child {
  transition: transform 0.3s ease;
}
.panel-enter-from > div:last-child,
.panel-leave-to > div:last-child {
  transform: translateX(100%);
}
.panel-enter-from,
.panel-leave-to {
  opacity: 0;
}

/* Modal transition */
.modal-enter-active,
.modal-leave-active {
  transition: all 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
.modal-enter-from > div:last-child,
.modal-leave-to > div:last-child {
  transform: scale(0.95);
}

/* Drag styles */
.sortable-ghost {
  opacity: 0.3;
}

/* Scrollbar styling */
::-webkit-scrollbar {
  width: 4px;
}
::-webkit-scrollbar-track {
  background: transparent;
}
::-webkit-scrollbar-thumb {
  background: #233f48;
  border-radius: 2px;
}
</style>
