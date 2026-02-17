<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { marked } from 'marked';

// State
const entries = ref([]);
const loading = ref(true);
const search = ref('');
const selectedCategory = ref(null);
const selectedEntry = ref(null);
const showForm = ref(false);
const editingEntry = ref(null);
const showPreview = ref(false);
const mobileSidebarOpen = ref(false);
const saving = ref(false);
const deleting = ref(false);

// Form
const form = ref({ title: '', content: '', category: '', tags: '' });

// Category colors
const categoryColors = [
  { bg: 'rgba(19,182,236,0.15)', text: '#13b6ec', border: 'rgba(19,182,236,0.3)' },
  { bg: 'rgba(168,85,247,0.15)', text: '#a855f7', border: 'rgba(168,85,247,0.3)' },
  { bg: 'rgba(34,197,94,0.15)', text: '#22c55e', border: 'rgba(34,197,94,0.3)' },
  { bg: 'rgba(249,115,22,0.15)', text: '#f97316', border: 'rgba(249,115,22,0.3)' },
  { bg: 'rgba(236,72,153,0.15)', text: '#ec4899', border: 'rgba(236,72,153,0.3)' },
  { bg: 'rgba(234,179,8,0.15)', text: '#eab308', border: 'rgba(234,179,8,0.3)' },
  { bg: 'rgba(99,102,241,0.15)', text: '#6366f1', border: 'rgba(99,102,241,0.3)' },
  { bg: 'rgba(20,184,166,0.15)', text: '#14b8a6', border: 'rgba(20,184,166,0.3)' },
];

function hashStr(s) {
  let h = 0;
  for (let i = 0; i < s.length; i++) h = ((h << 5) - h + s.charCodeAt(i)) | 0;
  return Math.abs(h);
}

function getCategoryColor(cat) {
  if (!cat) return categoryColors[0];
  return categoryColors[hashStr(cat) % categoryColors.length];
}

// Computed
const categories = computed(() => {
  const map = {};
  entries.value.forEach(e => {
    const c = e.category || 'Uncategorized';
    map[c] = (map[c] || 0) + 1;
  });
  return Object.entries(map).sort((a, b) => a[0].localeCompare(b[0]));
});

const filteredEntries = computed(() => {
  let list = entries.value;
  if (selectedCategory.value) {
    list = list.filter(e => (e.category || 'Uncategorized') === selectedCategory.value);
  }
  if (search.value.trim()) {
    const q = search.value.toLowerCase();
    list = list.filter(e =>
      e.title?.toLowerCase().includes(q) ||
      e.content?.toLowerCase().includes(q) ||
      (e.tags || []).some(t => t.toLowerCase().includes(q))
    );
  }
  return list.sort((a, b) => new Date(b.updated_at) - new Date(a.updated_at));
});

// API
async function fetchEntries() {
  loading.value = true;
  try {
    const r = await fetch('/api/brain', { credentials: 'same-origin' });
    const j = await r.json();
    if (j.success) entries.value = j.data;
  } catch (e) { console.error(e); }
  loading.value = false;
}

async function saveEntry() {
  saving.value = true;
  const tags = form.value.tags ? form.value.tags.split(',').map(t => t.trim()).filter(Boolean) : [];
  const body = { title: form.value.title, content: form.value.content, category: form.value.category, tags };
  try {
    const url = editingEntry.value ? `/api/brain/${editingEntry.value.id}` : '/api/brain';
    const method = editingEntry.value ? 'PUT' : 'POST';
    const r = await fetch(url, {
      method, credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify(body),
    });
    const j = await r.json();
    if (j.success) {
      await fetchEntries();
      if (editingEntry.value && selectedEntry.value) {
        selectedEntry.value = entries.value.find(e => e.id === editingEntry.value.id) || null;
      }
      closeForm();
    }
  } catch (e) { console.error(e); }
  saving.value = false;
}

async function deleteEntry(entry) {
  if (!confirm(`Delete "${entry.title}"?`)) return;
  deleting.value = true;
  try {
    await fetch(`/api/brain/${entry.id}`, { method: 'DELETE', credentials: 'same-origin' });
    if (selectedEntry.value?.id === entry.id) selectedEntry.value = null;
    await fetchEntries();
  } catch (e) { console.error(e); }
  deleting.value = false;
}

// UI
function openCreate() {
  editingEntry.value = null;
  form.value = { title: '', content: '', category: selectedCategory.value || '', tags: '' };
  showPreview.value = false;
  showForm.value = true;
}

function openEdit(entry) {
  editingEntry.value = entry;
  form.value = {
    title: entry.title,
    content: entry.content || '',
    category: entry.category || '',
    tags: (entry.tags || []).join(', '),
  };
  showPreview.value = false;
  showForm.value = true;
}

function closeForm() {
  showForm.value = false;
  editingEntry.value = null;
}

function selectEntry(entry) {
  selectedEntry.value = entry;
}

function backToList() {
  selectedEntry.value = null;
}

function selectCategory(cat) {
  selectedCategory.value = cat;
  selectedEntry.value = null;
  mobileSidebarOpen.value = false;
}

function renderMarkdown(text) {
  if (!text) return '';
  return marked(text, { breaks: true });
}

function contentPreview(text) {
  if (!text) return '';
  const lines = text.split('\n').filter(l => l.trim()).slice(0, 2);
  const preview = lines.join(' ');
  return preview.length > 160 ? preview.slice(0, 160) + '…' : preview;
}

function formatDate(d) {
  if (!d) return '';
  return new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
}

onMounted(fetchEntries);
</script>

<template>
<AppLayout>
  <div class="brain-page">
    <!-- Top bar -->
    <div class="brain-topbar">
      <div class="brain-topbar-left">
        <button class="mobile-menu-btn" @click="mobileSidebarOpen = !mobileSidebarOpen">
          <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <h1 class="brain-title">
          <svg width="22" height="22" fill="none" stroke="#13b6ec" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2a7 7 0 0 1 7 7c0 2.5-1.3 4.7-3.2 6H8.2C6.3 13.7 5 11.5 5 9a7 7 0 0 1 7-7z"/><path d="M9 22h6M10 18h4"/></svg>
          Brain
        </h1>
      </div>
      <div class="brain-topbar-right">
        <div class="search-box">
          <svg class="search-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
          <input v-model="search" type="text" placeholder="Search knowledge base…" class="search-input" />
        </div>
        <button class="btn-create" @click="openCreate">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
          New Entry
        </button>
      </div>
    </div>

    <div class="brain-body">
      <!-- Sidebar -->
      <aside class="brain-sidebar" :class="{ open: mobileSidebarOpen }">
        <div class="sidebar-header">Categories</div>
        <div
          class="sidebar-item"
          :class="{ active: selectedCategory === null }"
          @click="selectCategory(null)"
        >
          <span>All</span>
          <span class="sidebar-count">{{ entries.length }}</span>
        </div>
        <div
          v-for="[cat, count] in categories"
          :key="cat"
          class="sidebar-item"
          :class="{ active: selectedCategory === cat }"
          @click="selectCategory(cat)"
        >
          <span class="sidebar-cat-dot" :style="{ background: getCategoryColor(cat).text }"></span>
          <span class="sidebar-cat-name">{{ cat }}</span>
          <span class="sidebar-count">{{ count }}</span>
        </div>
      </aside>

      <!-- Main content -->
      <main class="brain-main">
        <!-- Loading -->
        <div v-if="loading" class="brain-loading">
          <div class="spinner"></div>
        </div>

        <!-- Detail view -->
        <div v-else-if="selectedEntry && !showForm" class="entry-detail">
          <button class="btn-back" @click="backToList">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"/></svg>
            Back
          </button>
          <div class="entry-detail-card">
            <div class="entry-detail-header">
              <h2 class="entry-detail-title">{{ selectedEntry.title }}</h2>
              <div class="entry-detail-actions">
                <button class="btn-icon" @click="openEdit(selectedEntry)" title="Edit">
                  <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                </button>
                <button class="btn-icon btn-icon-danger" @click="deleteEntry(selectedEntry)" title="Delete">
                  <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14z"/></svg>
                </button>
              </div>
            </div>
            <div class="entry-detail-meta">
              <span
                class="category-badge"
                :style="{
                  background: getCategoryColor(selectedEntry.category).bg,
                  color: getCategoryColor(selectedEntry.category).text,
                  borderColor: getCategoryColor(selectedEntry.category).border,
                }"
              >{{ selectedEntry.category || 'Uncategorized' }}</span>
              <span v-for="tag in (selectedEntry.tags || [])" :key="tag" class="tag-pill">{{ tag }}</span>
              <span class="entry-date">{{ formatDate(selectedEntry.updated_at) }}</span>
            </div>
            <div class="entry-detail-content prose" v-html="renderMarkdown(selectedEntry.content)"></div>
          </div>
        </div>

        <!-- Form -->
        <div v-else-if="showForm" class="entry-form-wrap">
          <div class="entry-form-card">
            <div class="entry-form-header">
              <h2>{{ editingEntry ? 'Edit Entry' : 'New Entry' }}</h2>
              <button class="btn-icon" @click="closeForm">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
              </button>
            </div>
            <form @submit.prevent="saveEntry" class="entry-form">
              <div class="form-group">
                <label>Title</label>
                <input v-model="form.title" type="text" required placeholder="Entry title" />
              </div>
              <div class="form-row">
                <div class="form-group flex-1">
                  <label>Category</label>
                  <input v-model="form.category" type="text" list="cat-list" placeholder="e.g. SOP, Decision, Reference" />
                  <datalist id="cat-list">
                    <option v-for="[cat] in categories" :key="cat" :value="cat" />
                  </datalist>
                </div>
                <div class="form-group flex-1">
                  <label>Tags <span class="form-hint">(comma-separated)</span></label>
                  <input v-model="form.tags" type="text" placeholder="e.g. shipping, important" />
                </div>
              </div>
              <div class="form-group">
                <div class="form-label-row">
                  <label>Content <span class="form-hint">(Markdown)</span></label>
                  <button type="button" class="btn-toggle-preview" @click="showPreview = !showPreview">
                    {{ showPreview ? '✏️ Edit' : '👁 Preview' }}
                  </button>
                </div>
                <textarea v-if="!showPreview" v-model="form.content" rows="14" placeholder="Write your content in Markdown…"></textarea>
                <div v-else class="form-preview prose" v-html="renderMarkdown(form.content)"></div>
              </div>
              <div class="form-actions">
                <button type="button" class="btn-cancel" @click="closeForm">Cancel</button>
                <button type="submit" class="btn-save" :disabled="saving || !form.title.trim()">
                  {{ saving ? 'Saving…' : (editingEntry ? 'Update' : 'Create') }}
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- List view -->
        <div v-else-if="filteredEntries.length === 0 && !loading" class="brain-empty">
          <div class="empty-icon">📚</div>
          <h3>{{ entries.length === 0 ? 'Your knowledge base is empty' : 'No entries match your search' }}</h3>
          <p>{{ entries.length === 0 ? 'Start documenting!' : 'Try a different search or category.' }}</p>
          <button v-if="entries.length === 0" class="btn-create" @click="openCreate">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
            Create First Entry
          </button>
        </div>

        <div v-else class="entries-grid">
          <div
            v-for="entry in filteredEntries"
            :key="entry.id"
            class="entry-card"
            @click="selectEntry(entry)"
          >
            <div class="entry-card-top">
              <h3 class="entry-card-title">{{ entry.title }}</h3>
              <span
                class="category-badge small"
                :style="{
                  background: getCategoryColor(entry.category).bg,
                  color: getCategoryColor(entry.category).text,
                  borderColor: getCategoryColor(entry.category).border,
                }"
              >{{ entry.category || 'Uncategorized' }}</span>
            </div>
            <p class="entry-card-preview">{{ contentPreview(entry.content) }}</p>
            <div class="entry-card-footer">
              <div class="entry-card-tags">
                <span v-for="tag in (entry.tags || []).slice(0, 3)" :key="tag" class="tag-pill small">{{ tag }}</span>
                <span v-if="(entry.tags || []).length > 3" class="tag-pill small more">+{{ entry.tags.length - 3 }}</span>
              </div>
              <span class="entry-date">{{ formatDate(entry.updated_at) }}</span>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>

  <!-- Mobile sidebar overlay -->
  <div v-if="mobileSidebarOpen" class="sidebar-overlay" @click="mobileSidebarOpen = false"></div>
</AppLayout>
</template>

<style scoped>
.brain-page { display: flex; flex-direction: column; height: 100%; min-height: 0; }

/* Top bar */
.brain-topbar {
  display: flex; align-items: center; justify-content: space-between; gap: 12px;
  padding: 16px 24px; border-bottom: 1px solid #233f48; flex-shrink: 0; flex-wrap: wrap;
}
.brain-topbar-left { display: flex; align-items: center; gap: 12px; }
.brain-topbar-right { display: flex; align-items: center; gap: 12px; flex: 1; justify-content: flex-end; }
.brain-title { font-size: 18px; font-weight: 700; color: #e2e8f0; display: flex; align-items: center; gap: 8px; margin: 0; white-space: nowrap; }
.mobile-menu-btn { display: none; background: none; border: 1px solid #233f48; border-radius: 6px; color: #94a3b8; padding: 6px; cursor: pointer; }

/* Search */
.search-box { position: relative; flex: 1; max-width: 400px; }
.search-icon { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #64748b; }
.search-input {
  width: 100%; padding: 8px 12px 8px 34px; background: #0f1f24; border: 1px solid #233f48;
  border-radius: 8px; color: #e2e8f0; font-size: 14px; outline: none; transition: border-color 0.2s;
}
.search-input:focus { border-color: #13b6ec; }
.search-input::placeholder { color: #4a6670; }

/* Buttons */
.btn-create {
  display: flex; align-items: center; gap: 6px; padding: 8px 16px; background: #13b6ec;
  color: #fff; border: none; border-radius: 8px; font-size: 14px; font-weight: 600;
  cursor: pointer; white-space: nowrap; transition: background 0.2s;
}
.btn-create:hover { background: #0ea5d9; }
.btn-back {
  display: flex; align-items: center; gap: 4px; background: none; border: none;
  color: #94a3b8; font-size: 14px; cursor: pointer; padding: 4px 0; margin-bottom: 16px;
}
.btn-back:hover { color: #13b6ec; }
.btn-icon {
  background: none; border: 1px solid #233f48; border-radius: 6px; color: #94a3b8;
  padding: 6px; cursor: pointer; transition: all 0.2s;
}
.btn-icon:hover { border-color: #13b6ec; color: #13b6ec; }
.btn-icon-danger:hover { border-color: #ef4444; color: #ef4444; }

/* Body */
.brain-body { display: flex; flex: 1; min-height: 0; overflow: hidden; }

/* Sidebar */
.brain-sidebar {
  width: 220px; flex-shrink: 0; border-right: 1px solid #233f48; padding: 16px 0;
  overflow-y: auto; background: #0f1f24;
}
.sidebar-header { padding: 0 16px 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; }
.sidebar-item {
  display: flex; align-items: center; gap: 8px; padding: 8px 16px; cursor: pointer;
  color: #94a3b8; font-size: 14px; transition: all 0.15s;
}
.sidebar-item:hover { background: rgba(19,182,236,0.05); color: #e2e8f0; }
.sidebar-item.active { background: rgba(19,182,236,0.1); color: #13b6ec; }
.sidebar-cat-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.sidebar-cat-name { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.sidebar-count { font-size: 12px; color: #4a6670; margin-left: auto; }

/* Main */
.brain-main { flex: 1; overflow-y: auto; padding: 24px; }

/* Loading */
.brain-loading { display: flex; justify-content: center; padding: 60px; }
.spinner { width: 32px; height: 32px; border: 3px solid #233f48; border-top-color: #13b6ec; border-radius: 50%; animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/* Empty */
.brain-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 80px 20px; text-align: center; }
.empty-icon { font-size: 48px; margin-bottom: 16px; }
.brain-empty h3 { color: #e2e8f0; font-size: 18px; margin: 0 0 8px; }
.brain-empty p { color: #64748b; font-size: 14px; margin: 0 0 24px; }

/* Cards grid */
.entries-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 16px; }
.entry-card {
  background: #192d33; border: 1px solid #233f48; border-radius: 10px; padding: 18px;
  cursor: pointer; transition: all 0.2s; display: flex; flex-direction: column; gap: 10px;
}
.entry-card:hover { border-color: #13b6ec40; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
.entry-card-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; }
.entry-card-title { font-size: 15px; font-weight: 600; color: #e2e8f0; margin: 0; line-height: 1.4; }
.entry-card-preview { font-size: 13px; color: #7a9aa8; line-height: 1.5; margin: 0; flex: 1; }
.entry-card-footer { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
.entry-card-tags { display: flex; flex-wrap: wrap; gap: 4px; }
.entry-date { font-size: 11px; color: #4a6670; white-space: nowrap; }

/* Badges & pills */
.category-badge {
  display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 12px;
  font-weight: 600; border: 1px solid; white-space: nowrap;
}
.category-badge.small { padding: 2px 8px; font-size: 11px; }
.tag-pill {
  display: inline-block; padding: 2px 8px; background: rgba(148,163,184,0.1);
  color: #94a3b8; border-radius: 10px; font-size: 12px;
}
.tag-pill.small { font-size: 11px; padding: 1px 6px; }
.tag-pill.more { background: rgba(148,163,184,0.05); color: #64748b; }

/* Detail */
.entry-detail-card {
  background: #192d33; border: 1px solid #233f48; border-radius: 12px; padding: 28px;
}
.entry-detail-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 16px; }
.entry-detail-title { font-size: 22px; font-weight: 700; color: #e2e8f0; margin: 0; }
.entry-detail-actions { display: flex; gap: 8px; flex-shrink: 0; }
.entry-detail-meta { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid #233f48; }
.entry-detail-content { color: #c8d6dc; line-height: 1.7; font-size: 15px; }

/* Prose markdown styling */
.prose :deep(h1) { font-size: 24px; font-weight: 700; color: #e2e8f0; margin: 24px 0 12px; }
.prose :deep(h2) { font-size: 20px; font-weight: 600; color: #e2e8f0; margin: 20px 0 10px; }
.prose :deep(h3) { font-size: 17px; font-weight: 600; color: #e2e8f0; margin: 16px 0 8px; }
.prose :deep(p) { margin: 0 0 12px; }
.prose :deep(ul), .prose :deep(ol) { margin: 0 0 12px; padding-left: 24px; }
.prose :deep(li) { margin: 4px 0; }
.prose :deep(code) { background: #0f1f24; padding: 2px 6px; border-radius: 4px; font-size: 13px; color: #13b6ec; }
.prose :deep(pre) { background: #0f1f24; padding: 16px; border-radius: 8px; overflow-x: auto; margin: 0 0 12px; }
.prose :deep(pre code) { background: none; padding: 0; }
.prose :deep(blockquote) { border-left: 3px solid #13b6ec; padding-left: 16px; color: #94a3b8; margin: 0 0 12px; }
.prose :deep(a) { color: #13b6ec; text-decoration: underline; }
.prose :deep(table) { width: 100%; border-collapse: collapse; margin: 0 0 12px; }
.prose :deep(th), .prose :deep(td) { border: 1px solid #233f48; padding: 8px 12px; text-align: left; }
.prose :deep(th) { background: #0f1f24; color: #e2e8f0; font-weight: 600; }
.prose :deep(hr) { border: none; border-top: 1px solid #233f48; margin: 20px 0; }
.prose :deep(img) { max-width: 100%; border-radius: 8px; }

/* Form */
.entry-form-card {
  background: #192d33; border: 1px solid #233f48; border-radius: 12px; padding: 28px; max-width: 800px;
}
.entry-form-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
.entry-form-header h2 { font-size: 18px; font-weight: 700; color: #e2e8f0; margin: 0; }
.entry-form { display: flex; flex-direction: column; gap: 18px; }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-group label { font-size: 13px; font-weight: 600; color: #94a3b8; }
.form-hint { font-weight: 400; color: #4a6670; }
.form-row { display: flex; gap: 16px; }
.flex-1 { flex: 1; }
.form-group input, .form-group textarea {
  background: #0f1f24; border: 1px solid #233f48; border-radius: 8px; padding: 10px 14px;
  color: #e2e8f0; font-size: 14px; outline: none; transition: border-color 0.2s; font-family: inherit;
}
.form-group input:focus, .form-group textarea:focus { border-color: #13b6ec; }
.form-group textarea { resize: vertical; min-height: 200px; font-family: 'SF Mono', 'Fira Code', monospace; font-size: 13px; line-height: 1.6; }
.form-label-row { display: flex; align-items: center; justify-content: space-between; }
.btn-toggle-preview {
  background: none; border: 1px solid #233f48; border-radius: 6px; color: #94a3b8;
  font-size: 12px; padding: 4px 10px; cursor: pointer; transition: all 0.2s;
}
.btn-toggle-preview:hover { border-color: #13b6ec; color: #13b6ec; }
.form-preview {
  background: #0f1f24; border: 1px solid #233f48; border-radius: 8px; padding: 16px;
  min-height: 200px; color: #c8d6dc; font-size: 14px; line-height: 1.6;
}
.form-actions { display: flex; justify-content: flex-end; gap: 12px; padding-top: 8px; }
.btn-cancel {
  padding: 8px 20px; background: none; border: 1px solid #233f48; border-radius: 8px;
  color: #94a3b8; font-size: 14px; cursor: pointer; transition: all 0.2s;
}
.btn-cancel:hover { border-color: #94a3b8; }
.btn-save {
  padding: 8px 24px; background: #13b6ec; color: #fff; border: none; border-radius: 8px;
  font-size: 14px; font-weight: 600; cursor: pointer; transition: background 0.2s;
}
.btn-save:hover { background: #0ea5d9; }
.btn-save:disabled { opacity: 0.5; cursor: not-allowed; }

/* Mobile overlay */
.sidebar-overlay { display: none; }

/* Responsive */
@media (max-width: 768px) {
  .mobile-menu-btn { display: flex; }
  .brain-sidebar {
    position: fixed; left: 0; top: 0; bottom: 0; z-index: 50;
    transform: translateX(-100%); transition: transform 0.25s ease;
    width: 260px; padding-top: 60px;
  }
  .brain-sidebar.open { transform: translateX(0); }
  .sidebar-overlay { display: block; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 40; }
  .brain-topbar { padding: 12px 16px; }
  .brain-main { padding: 16px; }
  .entries-grid { grid-template-columns: 1fr; }
  .form-row { flex-direction: column; gap: 18px; }
  .search-box { max-width: none; }
}
</style>
