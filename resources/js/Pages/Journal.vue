<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { marked } from 'marked';

marked.setOptions({ breaks: true, gfm: true });

const entries = ref([]);
const loading = ref(true);
const searchQuery = ref('');
const showNewForm = ref(false);
const newTitle = ref('');
const newDescription = ref('');
const newTags = ref('');
const saving = ref(false);
const editingId = ref(null);
const editTitle = ref('');
const editDescription = ref('');
const editTags = ref('');
const deleteConfirmId = ref(null);

async function fetchEntries() {
  try {
    const res = await fetch('/api/tasks?board=journal', { credentials: 'same-origin' });
    const json = await res.json();
    if (json.success) {
      entries.value = json.data.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
    }
  } catch (e) {
    console.error('Failed to fetch journal entries:', e);
  } finally {
    loading.value = false;
  }
}

const filteredEntries = computed(() => {
  if (!searchQuery.value.trim()) return entries.value;
  const q = searchQuery.value.toLowerCase();
  return entries.value.filter(e =>
    (e.title || '').toLowerCase().includes(q) ||
    (e.description || '').toLowerCase().includes(q) ||
    parseTags(e).some(t => t.toLowerCase().includes(q))
  );
});

function parseTags(entry) {
  if (entry.tags) {
    if (Array.isArray(entry.tags)) return entry.tags;
    try { const p = JSON.parse(entry.tags); if (Array.isArray(p)) return p; } catch {}
    return entry.tags.split(',').map(t => t.trim()).filter(Boolean);
  }
  return [];
}

function renderMarkdown(text) {
  if (!text) return '';
  return marked(text);
}

function timeAgo(dateStr) {
  if (!dateStr) return '';
  const now = new Date();
  const date = new Date(dateStr);
  const diff = Math.floor((now - date) / 1000);
  if (diff < 60) return 'just now';
  if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
  if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
  if (diff < 604800) return `${Math.floor(diff / 86400)}d ago`;
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined });
}

function formatDate(dateStr) {
  if (!dateStr) return '';
  return new Date(dateStr).toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
}

async function createEntry() {
  if (!newTitle.value.trim()) return;
  saving.value = true;
  try {
    const tags = newTags.value.split(',').map(t => t.trim()).filter(Boolean);
    const res = await fetch('/api/tasks', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({
        title: newTitle.value.trim(),
        description: newDescription.value.trim(),
        board: 'journal',
        status: 'backlog',
        tags: tags.length ? JSON.stringify(tags) : null,
      }),
    });
    const json = await res.json();
    if (json.success || json.data) {
      newTitle.value = '';
      newDescription.value = '';
      newTags.value = '';
      showNewForm.value = false;
      await fetchEntries();
    }
  } catch (e) {
    console.error('Failed to create entry:', e);
  } finally {
    saving.value = false;
  }
}

function startEdit(entry) {
  editingId.value = entry.id;
  editTitle.value = entry.title || '';
  editDescription.value = entry.description || '';
  const tags = parseTags(entry);
  editTags.value = tags.join(', ');
}

function cancelEdit() {
  editingId.value = null;
}

async function saveEdit(entry) {
  saving.value = true;
  try {
    const tags = editTags.value.split(',').map(t => t.trim()).filter(Boolean);
    await fetch(`/api/tasks/${entry.id}`, {
      method: 'PUT',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({
        title: editTitle.value.trim(),
        description: editDescription.value.trim(),
        tags: tags.length ? JSON.stringify(tags) : null,
      }),
    });
    editingId.value = null;
    await fetchEntries();
  } catch (e) {
    console.error('Failed to update entry:', e);
  } finally {
    saving.value = false;
  }
}

async function deleteEntry(id) {
  try {
    await fetch(`/api/tasks/${id}`, {
      method: 'DELETE',
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json' },
    });
    deleteConfirmId.value = null;
    await fetchEntries();
  } catch (e) {
    console.error('Failed to delete entry:', e);
  }
}

onMounted(fetchEntries);
</script>

<template>
  <AppLayout>
    <div class="min-h-screen py-8 px-4 sm:px-6">
      <div class="max-w-3xl mx-auto">

        <!-- Header -->
        <div class="mb-10">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-3xl font-bold text-white tracking-tight flex items-center gap-3">
                <span class="text-3xl">📓</span>
                Journal
              </h1>
              <p class="mt-1 text-gray-400 text-sm">Thoughts, quotes & insights</p>
            </div>
            <button
              @click="showNewForm = !showNewForm"
              class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200"
              :class="showNewForm
                ? 'bg-gray-700 text-gray-300 hover:bg-gray-600'
                : 'bg-[#13b6ec] text-white hover:bg-[#0ea5d8] shadow-lg shadow-[#13b6ec]/20'"
            >
              <svg v-if="!showNewForm" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
              <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              {{ showNewForm ? 'Cancel' : 'New Entry' }}
            </button>
          </div>
        </div>

        <!-- New Entry Form -->
        <Transition
          enter-active-class="transition-all duration-300 ease-out"
          enter-from-class="opacity-0 -translate-y-4 scale-95"
          enter-to-class="opacity-100 translate-y-0 scale-100"
          leave-active-class="transition-all duration-200 ease-in"
          leave-from-class="opacity-100 translate-y-0 scale-100"
          leave-to-class="opacity-0 -translate-y-4 scale-95"
        >
          <div v-if="showNewForm" class="mb-8 bg-[#192d33] border border-[#233f48] rounded-2xl p-6 shadow-xl">
            <h3 class="text-sm font-medium text-gray-400 uppercase tracking-wider mb-4">New Journal Entry</h3>
            <input
              v-model="newTitle"
              type="text"
              placeholder="What's on your mind? A quote, insight, idea..."
              class="w-full bg-[#0f1f24] border border-[#233f48] rounded-xl px-4 py-3 text-white text-lg placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[#13b6ec]/50 focus:border-[#13b6ec] transition-all"
              @keydown.enter.meta="createEntry"
            />
            <textarea
              v-model="newDescription"
              placeholder="Add context, notes, or expand on your thought... (Markdown supported)"
              rows="4"
              class="mt-3 w-full bg-[#0f1f24] border border-[#233f48] rounded-xl px-4 py-3 text-gray-300 text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[#13b6ec]/50 focus:border-[#13b6ec] transition-all resize-none"
            />
            <input
              v-model="newTags"
              type="text"
              placeholder="Tags (comma-separated): stoicism, business, personal"
              class="mt-3 w-full bg-[#0f1f24] border border-[#233f48] rounded-xl px-4 py-3 text-gray-300 text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[#13b6ec]/50 focus:border-[#13b6ec] transition-all"
            />
            <div class="mt-4 flex items-center justify-between">
              <span class="text-xs text-gray-500">⌘+Enter to save</span>
              <button
                @click="createEntry"
                :disabled="!newTitle.trim() || saving"
                class="px-5 py-2.5 bg-[#13b6ec] text-white rounded-xl text-sm font-medium hover:bg-[#0ea5d8] disabled:opacity-40 disabled:cursor-not-allowed transition-all shadow-lg shadow-[#13b6ec]/20"
              >
                {{ saving ? 'Saving...' : 'Save Entry' }}
              </button>
            </div>
          </div>
        </Transition>

        <!-- Search -->
        <div class="mb-6">
          <div class="relative">
            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search entries..."
              class="w-full bg-[#192d33]/50 border border-[#233f48]/50 rounded-xl pl-10 pr-4 py-2.5 text-sm text-gray-300 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[#13b6ec]/30 focus:border-[#13b6ec]/50 transition-all"
            />
          </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-20">
          <div class="w-8 h-8 border-2 border-[#13b6ec]/30 border-t-[#13b6ec] rounded-full animate-spin"></div>
        </div>

        <!-- Empty State -->
        <div v-else-if="filteredEntries.length === 0 && !loading" class="text-center py-20">
          <div class="text-6xl mb-4">{{ searchQuery ? '🔍' : '✨' }}</div>
          <h3 class="text-xl font-semibold text-gray-300 mb-2">
            {{ searchQuery ? 'No entries found' : 'Your journal awaits' }}
          </h3>
          <p class="text-gray-500 text-sm max-w-md mx-auto">
            {{ searchQuery ? 'Try a different search term.' : 'Capture your first thought, quote, or insight. Every great journey starts with a single note.' }}
          </p>
          <button
            v-if="!searchQuery"
            @click="showNewForm = true"
            class="mt-6 px-5 py-2.5 bg-[#13b6ec] text-white rounded-xl text-sm font-medium hover:bg-[#0ea5d8] transition-all shadow-lg shadow-[#13b6ec]/20"
          >
            Write your first entry
          </button>
        </div>

        <!-- Entries Feed -->
        <div v-else class="space-y-4">
          <TransitionGroup
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 translate-y-4"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0 scale-95"
          >
            <div
              v-for="entry in filteredEntries"
              :key="entry.id"
              class="group relative bg-[#192d33] border border-[#233f48] rounded-2xl p-6 border-l-4 border-l-[#13b6ec]/60 hover:border-l-[#13b6ec] hover:shadow-lg hover:shadow-[#13b6ec]/5 transition-all duration-200"
            >
              <!-- View Mode -->
              <div v-if="editingId !== entry.id">
                <!-- Actions (hover reveal) -->
                <div class="absolute top-4 right-4 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                  <button
                    @click="startEdit(entry)"
                    class="p-2 text-gray-500 hover:text-[#13b6ec] hover:bg-[#13b6ec]/10 rounded-lg transition-all"
                    title="Edit"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                  </button>
                  <button
                    v-if="deleteConfirmId !== entry.id"
                    @click="deleteConfirmId = entry.id"
                    class="p-2 text-gray-500 hover:text-red-400 hover:bg-red-400/10 rounded-lg transition-all"
                    title="Delete"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                  </button>
                  <template v-else>
                    <button
                      @click="deleteEntry(entry.id)"
                      class="px-2.5 py-1.5 text-xs font-medium text-red-400 bg-red-400/10 hover:bg-red-400/20 rounded-lg transition-all"
                    >
                      Delete
                    </button>
                    <button
                      @click="deleteConfirmId = null"
                      class="px-2.5 py-1.5 text-xs font-medium text-gray-400 hover:bg-gray-700 rounded-lg transition-all"
                    >
                      Cancel
                    </button>
                  </template>
                </div>

                <!-- Date -->
                <div class="flex items-center gap-2 mb-3">
                  <span class="text-xs text-gray-500" :title="formatDate(entry.created_at)">
                    {{ timeAgo(entry.created_at) }}
                  </span>
                </div>

                <!-- Title -->
                <h2 class="text-xl font-bold text-white leading-snug pr-20">
                  {{ entry.title }}
                </h2>

                <!-- Description -->
                <div
                  v-if="entry.description"
                  class="mt-3 prose prose-sm prose-invert prose-p:text-gray-400 prose-a:text-[#13b6ec] prose-strong:text-gray-300 prose-code:text-[#13b6ec] prose-code:bg-[#0f1f24] prose-code:px-1.5 prose-code:py-0.5 prose-code:rounded max-w-none text-gray-400 leading-relaxed"
                  v-html="renderMarkdown(entry.description)"
                />

                <!-- Tags -->
                <div v-if="parseTags(entry).length" class="mt-4 flex flex-wrap gap-2">
                  <span
                    v-for="tag in parseTags(entry)"
                    :key="tag"
                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-[#13b6ec]/10 text-[#13b6ec]/80"
                  >
                    #{{ tag }}
                  </span>
                </div>
              </div>

              <!-- Edit Mode -->
              <div v-else>
                <h3 class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-3">Editing Entry</h3>
                <input
                  v-model="editTitle"
                  type="text"
                  class="w-full bg-[#0f1f24] border border-[#233f48] rounded-xl px-4 py-3 text-white text-lg focus:outline-none focus:ring-2 focus:ring-[#13b6ec]/50 focus:border-[#13b6ec] transition-all"
                />
                <textarea
                  v-model="editDescription"
                  rows="4"
                  class="mt-3 w-full bg-[#0f1f24] border border-[#233f48] rounded-xl px-4 py-3 text-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-[#13b6ec]/50 focus:border-[#13b6ec] transition-all resize-none"
                />
                <input
                  v-model="editTags"
                  type="text"
                  placeholder="Tags (comma-separated)"
                  class="mt-3 w-full bg-[#0f1f24] border border-[#233f48] rounded-xl px-4 py-3 text-gray-300 text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[#13b6ec]/50 focus:border-[#13b6ec] transition-all"
                />
                <div class="mt-4 flex items-center justify-end gap-2">
                  <button
                    @click="cancelEdit"
                    class="px-4 py-2 text-sm text-gray-400 hover:text-white hover:bg-gray-700 rounded-xl transition-all"
                  >
                    Cancel
                  </button>
                  <button
                    @click="saveEdit(entry)"
                    :disabled="!editTitle.trim() || saving"
                    class="px-5 py-2 bg-[#13b6ec] text-white rounded-xl text-sm font-medium hover:bg-[#0ea5d8] disabled:opacity-40 disabled:cursor-not-allowed transition-all"
                  >
                    {{ saving ? 'Saving...' : 'Save' }}
                  </button>
                </div>
              </div>
            </div>
          </TransitionGroup>
        </div>

        <!-- Entry count -->
        <div v-if="!loading && entries.length > 0" class="mt-8 text-center">
          <span class="text-xs text-gray-600">
            {{ filteredEntries.length }} {{ filteredEntries.length === 1 ? 'entry' : 'entries' }}
            <template v-if="searchQuery && filteredEntries.length !== entries.length">
              of {{ entries.length }}
            </template>
          </span>
        </div>

      </div>
    </div>
  </AppLayout>
</template>
