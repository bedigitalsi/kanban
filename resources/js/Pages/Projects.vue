<script setup>
import { ref, computed, onMounted } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const projects = ref([]);
const loading = ref(true);
const search = ref('');
const statusFilter = ref('all');
const companyFilter = ref('all');
const showModal = ref(false);
const editingProject = ref(null);
const expandedId = ref(null);

const form = ref(emptyForm());

function emptyForm() {
  return { name: '', description: '', status: 'active', repo_url: '', live_url: '', tech_stack: '', company: '' };
}

const statusColors = {
  active: { badge: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30', gradient: 'from-emerald-500' },
  paused: { badge: 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30', gradient: 'from-yellow-500' },
  completed: { badge: 'bg-blue-500/20 text-blue-400 border-blue-500/30', gradient: 'from-blue-500' },
  archived: { badge: 'bg-gray-500/20 text-gray-400 border-gray-500/30', gradient: 'from-gray-500' },
};

const companies = computed(() => {
  const set = new Set(projects.value.map(p => p.company).filter(Boolean));
  return [...set].sort();
});

const filtered = computed(() => {
  return projects.value.filter(p => {
    if (statusFilter.value !== 'all' && p.status !== statusFilter.value) return false;
    if (companyFilter.value !== 'all' && p.company !== companyFilter.value) return false;
    if (search.value) {
      const q = search.value.toLowerCase();
      return (p.name?.toLowerCase().includes(q) || p.description?.toLowerCase().includes(q) || p.company?.toLowerCase().includes(q) || (p.tech_stack || []).some(t => t.toLowerCase().includes(q)));
    }
    return true;
  });
});

async function fetchProjects() {
  loading.value = true;
  try {
    const r = await fetch('/api/projects', { credentials: 'same-origin' });
    const json = await r.json();
    projects.value = json.data || [];
  } catch (e) { console.error(e); }
  loading.value = false;
}

function openAdd() {
  editingProject.value = null;
  form.value = emptyForm();
  showModal.value = true;
}

function openEdit(p) {
  editingProject.value = p;
  form.value = {
    name: p.name || '',
    description: p.description || '',
    status: p.status || 'active',
    repo_url: p.repo_url || '',
    live_url: p.live_url || '',
    tech_stack: (p.tech_stack || []).join(', '),
    company: p.company || '',
  };
  showModal.value = true;
}

async function saveProject() {
  const body = {
    ...form.value,
    tech_stack: form.value.tech_stack.split(',').map(s => s.trim()).filter(Boolean),
  };
  const url = editingProject.value ? `/api/projects/${editingProject.value.id}` : '/api/projects';
  const method = editingProject.value ? 'PUT' : 'POST';
  await fetch(url, { method, credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify(body) });
  showModal.value = false;
  await fetchProjects();
}

async function deleteProject(id) {
  if (!confirm('Delete this project?')) return;
  await fetch(`/api/projects/${id}`, { method: 'DELETE', credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
  expandedId.value = null;
  await fetchProjects();
}

function toggleExpand(id) {
  expandedId.value = expandedId.value === id ? null : id;
}

function formatDate(d) {
  if (!d) return '';
  return new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
}

onMounted(fetchProjects);
</script>

<template>
  <AppLayout>
    <div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
          <h1 class="text-2xl font-bold text-white">Projects</h1>
          <p class="text-sm text-gray-400 mt-1">{{ filtered.length }} project{{ filtered.length !== 1 ? 's' : '' }}</p>
        </div>
        <button @click="openAdd" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-[#13b6ec] hover:bg-[#0fa3d6] text-white font-medium transition-colors text-sm cursor-pointer">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Add Project
        </button>
      </div>

      <!-- Filter bar -->
      <div class="flex flex-col sm:flex-row gap-3 mb-6">
        <div class="relative flex-1">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          <input v-model="search" placeholder="Search projects..." class="w-full pl-10 pr-4 py-2 rounded-lg bg-[#192d33] border border-[#233f48] text-white placeholder-gray-500 text-sm focus:outline-none focus:border-[#13b6ec]" />
        </div>
        <select v-model="statusFilter" class="px-3 py-2 rounded-lg bg-[#192d33] border border-[#233f48] text-white text-sm focus:outline-none focus:border-[#13b6ec] cursor-pointer">
          <option value="all">All statuses</option>
          <option value="active">Active</option>
          <option value="paused">Paused</option>
          <option value="completed">Completed</option>
          <option value="archived">Archived</option>
        </select>
        <select v-model="companyFilter" class="px-3 py-2 rounded-lg bg-[#192d33] border border-[#233f48] text-white text-sm focus:outline-none focus:border-[#13b6ec] cursor-pointer">
          <option value="all">All companies</option>
          <option v-for="c in companies" :key="c" :value="c">{{ c }}</option>
        </select>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="text-center py-20 text-gray-400">Loading...</div>

      <!-- Empty -->
      <div v-else-if="!filtered.length" class="text-center py-20">
        <p class="text-gray-500">No projects found</p>
      </div>

      <!-- Grid -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        <div v-for="p in filtered" :key="p.id" class="group">
          <!-- Card -->
          <div
            @click="toggleExpand(p.id)"
            class="bg-[#192d33] border border-[#233f48] rounded-xl overflow-hidden cursor-pointer transition-all duration-200 hover:border-[#13b6ec]/40 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-black/20"
          >
            <!-- Status gradient top -->
            <div :class="`h-1 bg-gradient-to-r ${statusColors[p.status]?.gradient || 'from-gray-500'} to-transparent`"></div>

            <div class="p-4">
              <!-- Header row -->
              <div class="flex items-start justify-between gap-2 mb-2">
                <h3 class="font-semibold text-white truncate">{{ p.name }}</h3>
                <span :class="`shrink-0 text-xs px-2 py-0.5 rounded-full border ${statusColors[p.status]?.badge}`">{{ p.status }}</span>
              </div>

              <!-- Company -->
              <p v-if="p.company" class="text-xs text-gray-500 mb-2">{{ p.company }}</p>

              <!-- Description -->
              <p class="text-sm text-gray-400 mb-3 line-clamp-2">{{ p.description || 'No description' }}</p>

              <!-- Tech stack -->
              <div v-if="p.tech_stack?.length" class="flex flex-wrap gap-1.5 mb-3">
                <span v-for="t in p.tech_stack.slice(0, 5)" :key="t" class="text-[10px] px-1.5 py-0.5 rounded bg-[#233f48] text-gray-300">{{ t }}</span>
                <span v-if="p.tech_stack.length > 5" class="text-[10px] px-1.5 py-0.5 rounded bg-[#233f48] text-gray-500">+{{ p.tech_stack.length - 5 }}</span>
              </div>

              <!-- Links -->
              <div class="flex items-center gap-3">
                <a v-if="p.repo_url" :href="p.repo_url" target="_blank" @click.stop class="text-gray-500 hover:text-white transition-colors" title="Repository">
                  <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.3 3.44 9.8 8.2 11.39.6.11.82-.26.82-.58v-2.03c-3.34.73-4.04-1.61-4.04-1.61-.55-1.39-1.34-1.76-1.34-1.76-1.09-.75.08-.73.08-.73 1.2.08 1.84 1.24 1.84 1.24 1.07 1.84 2.81 1.31 3.5 1 .11-.78.42-1.31.76-1.61-2.67-.3-5.47-1.33-5.47-5.93 0-1.31.47-2.38 1.24-3.22-.13-.3-.54-1.52.12-3.18 0 0 1-.32 3.3 1.23a11.5 11.5 0 016.02 0c2.28-1.55 3.29-1.23 3.29-1.23.66 1.66.25 2.88.12 3.18.77.84 1.24 1.91 1.24 3.22 0 4.61-2.81 5.63-5.48 5.92.43.37.81 1.1.81 2.22v3.29c0 .32.22.7.82.58C20.57 21.8 24 17.3 24 12 24 5.37 18.63 0 12 0z"/></svg>
                </a>
                <a v-if="p.live_url" :href="p.live_url" target="_blank" @click.stop class="text-gray-500 hover:text-white transition-colors" title="Live site">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>
                <span class="ml-auto text-[10px] text-gray-600">{{ formatDate(p.created_at) }}</span>
              </div>
            </div>
          </div>

          <!-- Expanded detail -->
          <div v-if="expandedId === p.id" class="mt-1 bg-[#192d33] border border-[#233f48] rounded-xl p-5 space-y-4 animate-[fadeIn_0.15s_ease]">
            <div class="flex items-center justify-between">
              <h2 class="text-lg font-bold text-white">{{ p.name }}</h2>
              <div class="flex gap-2">
                <button @click.stop="openEdit(p)" class="text-xs px-3 py-1 rounded bg-[#13b6ec]/20 text-[#13b6ec] hover:bg-[#13b6ec]/30 transition-colors cursor-pointer">Edit</button>
                <button @click.stop="deleteProject(p.id)" class="text-xs px-3 py-1 rounded bg-red-500/20 text-red-400 hover:bg-red-500/30 transition-colors cursor-pointer">Delete</button>
              </div>
            </div>
            <p class="text-sm text-gray-300 whitespace-pre-line">{{ p.description || 'No description' }}</p>
            <div class="grid grid-cols-2 gap-3 text-sm">
              <div><span class="text-gray-500">Status:</span> <span :class="statusColors[p.status]?.badge" class="ml-1 text-xs px-2 py-0.5 rounded-full border">{{ p.status }}</span></div>
              <div><span class="text-gray-500">Company:</span> <span class="text-gray-300 ml-1">{{ p.company || '—' }}</span></div>
              <div v-if="p.repo_url"><span class="text-gray-500">Repo:</span> <a :href="p.repo_url" target="_blank" class="text-[#13b6ec] hover:underline ml-1 text-xs break-all">{{ p.repo_url }}</a></div>
              <div v-if="p.live_url"><span class="text-gray-500">Live:</span> <a :href="p.live_url" target="_blank" class="text-[#13b6ec] hover:underline ml-1 text-xs break-all">{{ p.live_url }}</a></div>
            </div>
            <div v-if="p.tech_stack?.length">
              <span class="text-gray-500 text-sm">Tech Stack:</span>
              <div class="flex flex-wrap gap-1.5 mt-1">
                <span v-for="t in p.tech_stack" :key="t" class="text-xs px-2 py-0.5 rounded bg-[#233f48] text-gray-300">{{ t }}</span>
              </div>
            </div>
            <p class="text-xs text-gray-600">Created {{ formatDate(p.created_at) }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showModal = false">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showModal = false"></div>
        <div class="relative w-full max-w-lg bg-[#192d33] border border-[#233f48] rounded-xl p-6 space-y-4 max-h-[90vh] overflow-y-auto">
          <h2 class="text-lg font-bold text-white">{{ editingProject ? 'Edit Project' : 'New Project' }}</h2>

          <div class="space-y-3">
            <div>
              <label class="block text-xs text-gray-400 mb-1">Name *</label>
              <input v-model="form.name" class="w-full px-3 py-2 rounded-lg bg-[#0f1f24] border border-[#233f48] text-white text-sm focus:outline-none focus:border-[#13b6ec]" />
            </div>
            <div>
              <label class="block text-xs text-gray-400 mb-1">Description</label>
              <textarea v-model="form.description" rows="3" class="w-full px-3 py-2 rounded-lg bg-[#0f1f24] border border-[#233f48] text-white text-sm focus:outline-none focus:border-[#13b6ec] resize-none"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs text-gray-400 mb-1">Status</label>
                <select v-model="form.status" class="w-full px-3 py-2 rounded-lg bg-[#0f1f24] border border-[#233f48] text-white text-sm focus:outline-none focus:border-[#13b6ec] cursor-pointer">
                  <option value="active">Active</option>
                  <option value="paused">Paused</option>
                  <option value="completed">Completed</option>
                  <option value="archived">Archived</option>
                </select>
              </div>
              <div>
                <label class="block text-xs text-gray-400 mb-1">Company</label>
                <input v-model="form.company" placeholder="e.g. BeDigital" class="w-full px-3 py-2 rounded-lg bg-[#0f1f24] border border-[#233f48] text-white text-sm placeholder-gray-600 focus:outline-none focus:border-[#13b6ec]" />
              </div>
            </div>
            <div>
              <label class="block text-xs text-gray-400 mb-1">Repository URL</label>
              <input v-model="form.repo_url" placeholder="https://github.com/..." class="w-full px-3 py-2 rounded-lg bg-[#0f1f24] border border-[#233f48] text-white text-sm placeholder-gray-600 focus:outline-none focus:border-[#13b6ec]" />
            </div>
            <div>
              <label class="block text-xs text-gray-400 mb-1">Live URL</label>
              <input v-model="form.live_url" placeholder="https://..." class="w-full px-3 py-2 rounded-lg bg-[#0f1f24] border border-[#233f48] text-white text-sm placeholder-gray-600 focus:outline-none focus:border-[#13b6ec]" />
            </div>
            <div>
              <label class="block text-xs text-gray-400 mb-1">Tech Stack <span class="text-gray-600">(comma separated)</span></label>
              <input v-model="form.tech_stack" placeholder="Vue, Laravel, Tailwind" class="w-full px-3 py-2 rounded-lg bg-[#0f1f24] border border-[#233f48] text-white text-sm placeholder-gray-600 focus:outline-none focus:border-[#13b6ec]" />
            </div>
          </div>

          <div class="flex justify-end gap-3 pt-2">
            <button @click="showModal = false" class="px-4 py-2 rounded-lg text-sm text-gray-400 hover:text-white transition-colors cursor-pointer">Cancel</button>
            <button @click="saveProject" :disabled="!form.name.trim()" class="px-4 py-2 rounded-lg bg-[#13b6ec] hover:bg-[#0fa3d6] disabled:opacity-40 disabled:cursor-not-allowed text-white text-sm font-medium transition-colors cursor-pointer">
              {{ editingProject ? 'Update' : 'Create' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </AppLayout>
</template>
