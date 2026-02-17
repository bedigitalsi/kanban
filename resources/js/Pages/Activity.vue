<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed, watch, onMounted } from 'vue';

const TYPE_ICONS = { email: '📧', sms: '💬', order_fix: '🔧', analysis: '📊', integration: '🔌', other: '📝' };
const TYPE_COLORS = { email: '#3b82f6', sms: '#10b981', order_fix: '#f59e0b', analysis: '#8b5cf6', integration: '#ec4899', other: '#6b7280' };
const TYPES = Object.keys(TYPE_ICONS);

const logs = ref([]);
const meta = ref({ current_page: 0, last_page: 1, total: 0 });
const loading = ref(false);
const filterDate = ref('');
const filterType = ref('');
const searchText = ref('');
const expandedIds = ref(new Set());

// New log form
const showForm = ref(false);
const newLog = ref({ title: '', type: 'other', description: '' });
const saving = ref(false);

const stats = computed(() => {
  const counts = {};
  TYPES.forEach(t => counts[t] = 0);
  logs.value.forEach(l => { if (counts[l.type] !== undefined) counts[l.type]++; });
  return counts;
});

const grouped = computed(() => {
  const groups = {};
  logs.value.forEach(log => {
    const d = log.created_at?.slice(0, 10) || 'Unknown';
    (groups[d] ||= []).push(log);
  });
  return Object.entries(groups).sort((a, b) => b[0].localeCompare(a[0]));
});

function relativeTime(dateStr) {
  const diff = Date.now() - new Date(dateStr).getTime();
  const mins = Math.floor(diff / 60000);
  if (mins < 1) return 'just now';
  if (mins < 60) return `${mins}m ago`;
  const hrs = Math.floor(mins / 60);
  if (hrs < 24) return `${hrs}h ago`;
  const days = Math.floor(hrs / 24);
  return `${days}d ago`;
}

function formatDate(d) {
  const today = new Date().toISOString().slice(0, 10);
  const yesterday = new Date(Date.now() - 86400000).toISOString().slice(0, 10);
  if (d === today) return 'Today';
  if (d === yesterday) return 'Yesterday';
  return new Date(d + 'T00:00:00').toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
}

async function fetchLogs(page = 1, append = false) {
  loading.value = true;
  try {
    const params = new URLSearchParams({ page });
    if (filterDate.value) params.set('date', filterDate.value);
    if (filterType.value) params.set('type', filterType.value);
    const res = await fetch(`/api/activity-logs?${params}`, { credentials: 'same-origin' });
    const json = await res.json();
    if (json.success) {
      logs.value = append ? [...logs.value, ...json.data] : json.data;
      meta.value = json.meta;
    }
  } catch (e) { console.error(e); }
  loading.value = false;
}

function loadMore() {
  if (meta.value.current_page < meta.value.last_page) {
    fetchLogs(meta.value.current_page + 1, true);
  }
}

async function createLog() {
  if (!newLog.value.title.trim()) return;
  saving.value = true;
  try {
    await fetch('/api/activity-logs', {
      method: 'POST', credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify(newLog.value),
    });
    newLog.value = { title: '', type: 'other', description: '' };
    showForm.value = false;
    fetchLogs(1);
  } catch (e) { console.error(e); }
  saving.value = false;
}

function toggle(id) {
  expandedIds.value.has(id) ? expandedIds.value.delete(id) : expandedIds.value.add(id);
}

const filteredGrouped = computed(() => {
  if (!searchText.value.trim()) return grouped.value;
  const q = searchText.value.toLowerCase();
  return grouped.value.map(([date, items]) => [date, items.filter(l =>
    l.title?.toLowerCase().includes(q) || l.description?.toLowerCase().includes(q)
  )]).filter(([, items]) => items.length > 0);
});

let debounceTimer;
watch([filterDate, filterType], () => { fetchLogs(1); });
watch(searchText, () => { clearTimeout(debounceTimer); debounceTimer = setTimeout(() => {}, 300); });

onMounted(() => fetchLogs(1));
</script>

<template>
  <AppLayout>
    <div class="max-w-[800px] mx-auto px-4 py-8">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-white">Activity Log</h1>
        <button @click="showForm = !showForm"
          class="px-4 py-2 rounded-lg text-sm font-medium transition"
          :style="{ background: '#13b6ec', color: '#fff' }">
          {{ showForm ? '✕ Cancel' : '+ New Log' }}
        </button>
      </div>

      <!-- New Log Form -->
      <div v-if="showForm" class="mb-6 p-4 rounded-xl" style="background: #192d33; border: 1px solid #233f48;">
        <div class="grid gap-3">
          <input v-model="newLog.title" placeholder="Title" class="w-full px-3 py-2 rounded-lg bg-black/30 border border-white/10 text-white text-sm placeholder-white/40 focus:outline-none focus:border-[#13b6ec]" />
          <select v-model="newLog.type" class="px-3 py-2 rounded-lg bg-black/30 border border-white/10 text-white text-sm focus:outline-none focus:border-[#13b6ec]">
            <option v-for="t in TYPES" :key="t" :value="t">{{ TYPE_ICONS[t] }} {{ t.replace('_', ' ') }}</option>
          </select>
          <textarea v-model="newLog.description" placeholder="Description (optional)" rows="3" class="w-full px-3 py-2 rounded-lg bg-black/30 border border-white/10 text-white text-sm placeholder-white/40 focus:outline-none focus:border-[#13b6ec] resize-none" />
          <button @click="createLog" :disabled="saving || !newLog.title.trim()"
            class="px-4 py-2 rounded-lg text-sm font-medium text-white disabled:opacity-40 transition"
            style="background: #13b6ec;">
            {{ saving ? 'Saving...' : 'Save Log' }}
          </button>
        </div>
      </div>

      <!-- Stats -->
      <div class="mb-6 p-4 rounded-xl" style="background: #192d33; border: 1px solid #233f48;">
        <div class="flex items-center flex-wrap gap-2">
          <span class="text-white/60 text-sm mr-2">{{ meta.total }} logs</span>
          <span v-for="t in TYPES" :key="t" v-show="stats[t] > 0"
            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs text-white/90"
            :style="{ background: TYPE_COLORS[t] + '30', border: `1px solid ${TYPE_COLORS[t]}50` }">
            {{ TYPE_ICONS[t] }} {{ stats[t] }}
          </span>
        </div>
      </div>

      <!-- Filters -->
      <div class="mb-6 flex flex-wrap gap-3">
        <input type="date" v-model="filterDate"
          class="px-3 py-2 rounded-lg bg-black/30 border border-white/10 text-white text-sm focus:outline-none focus:border-[#13b6ec]" />
        <select v-model="filterType"
          class="px-3 py-2 rounded-lg bg-black/30 border border-white/10 text-white text-sm focus:outline-none focus:border-[#13b6ec]">
          <option value="">All types</option>
          <option v-for="t in TYPES" :key="t" :value="t">{{ TYPE_ICONS[t] }} {{ t.replace('_', ' ') }}</option>
        </select>
        <input v-model="searchText" placeholder="Search..." class="flex-1 min-w-[150px] px-3 py-2 rounded-lg bg-black/30 border border-white/10 text-white text-sm placeholder-white/40 focus:outline-none focus:border-[#13b6ec]" />
      </div>

      <!-- Timeline -->
      <div v-if="filteredGrouped.length > 0" class="relative">
        <!-- Vertical line -->
        <div class="absolute left-[11px] top-0 bottom-0 w-0.5 bg-white/10"></div>

        <div v-for="[date, items] in filteredGrouped" :key="date" class="mb-8">
          <!-- Date header -->
          <div class="relative flex items-center mb-4 pl-8">
            <div class="absolute left-[6px] w-3 h-3 rounded-full border-2" style="border-color: #13b6ec; background: #192d33;"></div>
            <span class="text-sm font-semibold text-white/70">{{ formatDate(date) }}</span>
          </div>

          <!-- Entries -->
          <div v-for="log in items" :key="log.id" class="relative pl-8 mb-3">
            <div class="absolute left-[9px] top-4 w-[7px] h-[7px] rounded-full" :style="{ background: TYPE_COLORS[log.type] || '#6b7280' }"></div>
            <div class="p-3 rounded-xl cursor-pointer transition hover:border-white/20"
              style="background: #192d33; border: 1px solid #233f48;"
              @click="toggle(log.id)">
              <div class="flex items-start justify-between gap-2">
                <div class="flex items-center gap-2 min-w-0">
                  <span class="text-base shrink-0">{{ TYPE_ICONS[log.type] }}</span>
                  <span class="text-sm font-medium text-white truncate">{{ log.title }}</span>
                  <span class="shrink-0 px-2 py-0.5 rounded-full text-[10px] font-medium text-white/80"
                    :style="{ background: TYPE_COLORS[log.type] + '40' }">
                    {{ log.type?.replace('_', ' ') }}
                  </span>
                </div>
                <span class="text-xs text-white/40 shrink-0 whitespace-nowrap">{{ relativeTime(log.created_at) }}</span>
              </div>
              <div v-if="expandedIds.has(log.id) && log.description" class="mt-2 text-sm text-white/60 whitespace-pre-wrap border-t border-white/5 pt-2">
                {{ log.description }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty state -->
      <div v-else-if="!loading" class="text-center py-16">
        <div class="text-4xl mb-3">📭</div>
        <p class="text-white/50 text-sm">No activity logs found</p>
        <p class="text-white/30 text-xs mt-1">Try adjusting your filters</p>
      </div>

      <!-- Load more -->
      <div v-if="meta.current_page < meta.last_page" class="text-center mt-6">
        <button @click="loadMore" :disabled="loading"
          class="px-6 py-2 rounded-lg text-sm font-medium text-white/80 transition hover:text-white disabled:opacity-40"
          style="background: #233f48;">
          {{ loading ? 'Loading...' : 'Load More' }}
        </button>
      </div>

      <!-- Loading -->
      <div v-if="loading && logs.length === 0" class="text-center py-16">
        <div class="text-white/40 text-sm">Loading...</div>
      </div>
    </div>
  </AppLayout>
</template>
