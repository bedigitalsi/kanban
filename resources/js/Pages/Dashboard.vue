<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed, onMounted } from 'vue';

const tasks = ref([]);
const journal = ref([]);
const activityLogs = ref([]);
const loading = ref(true);

const greeting = computed(() => {
  const h = new Date().getHours();
  if (h < 12) return 'Good morning';
  if (h < 18) return 'Good afternoon';
  return 'Good evening';
});

const today = computed(() =>
  new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
);

const tasksInProgress = computed(() => tasks.value.filter(t => t.status === 'in_progress').length);
const tasksBacklog = computed(() => tasks.value.filter(t => t.status === 'backlog').length);
const journalCount = computed(() => journal.value.length);
const logsToday = computed(() => {
  const todayStr = new Date().toISOString().slice(0, 10);
  return activityLogs.value.filter(l => (l.created_at || '').slice(0, 10) === todayStr).length;
});

const recentActivity = computed(() => activityLogs.value.slice(0, 5));
const activeTasks = computed(() =>
  tasks.value.filter(t => t.status === 'in_progress' || t.status === 'todo').slice(0, 5)
);
const latestJournal = computed(() => journal.value[0] || null);

const priorityColor = (p) => {
  const map = { urgent: '#ef4444', high: '#f59e0b', medium: '#13b6ec', low: '#6b7280' };
  return map[p] || '#6b7280';
};

const typeIcon = (type) => {
  const map = {
    created: '✨', updated: '✏️', deleted: '🗑️', completed: '✅',
    moved: '📦', commented: '💬', status_changed: '🔄',
  };
  return map[type] || '📋';
};

const timeAgo = (date) => {
  if (!date) return '';
  const diff = (Date.now() - new Date(date).getTime()) / 1000;
  if (diff < 60) return 'just now';
  if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
  if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
  return `${Math.floor(diff / 86400)}d ago`;
};

const fetchJSON = async (url) => {
  try {
    const r = await fetch(url, { credentials: 'same-origin' });
    const j = await r.json();
    return j.success ? j.data : [];
  } catch { return []; }
};

onMounted(async () => {
  const [t, j, a] = await Promise.all([
    fetchJSON('/api/tasks?board=tasks'),
    fetchJSON('/api/tasks?board=journal'),
    fetchJSON('/api/activity-logs'),
  ]);
  tasks.value = t;
  journal.value = j;
  activityLogs.value = a;
  loading.value = false;
});

const stats = computed(() => [
  { label: 'In Progress', value: tasksInProgress.value, icon: '🔥', color: '#f59e0b' },
  { label: 'Backlog', value: tasksBacklog.value, icon: '📋', color: '#13b6ec' },
  { label: 'Journal Entries', value: journalCount.value, icon: '📓', color: '#8b5cf6' },
  { label: 'Logs Today', value: logsToday.value, icon: '📊', color: '#10b981' },
]);
</script>

<template>
  <AppLayout>
    <div class="dashboard-root" :class="{ loaded: !loading }">
      <!-- Welcome -->
      <div class="welcome">
        <h1>{{ greeting }}, Sandi</h1>
        <p class="date">{{ today }}</p>
      </div>

      <!-- Stats -->
      <div class="stats-row">
        <div v-for="s in stats" :key="s.label" class="stat-card">
          <div class="stat-icon">{{ s.icon }}</div>
          <div class="stat-info">
            <span class="stat-value" :style="{ color: s.color }">{{ s.value }}</span>
            <span class="stat-label">{{ s.label }}</span>
          </div>
        </div>
      </div>

      <!-- Grid -->
      <div class="grid-2col">
        <!-- Active Tasks -->
        <div class="card">
          <div class="card-header">
            <h2>🎯 Active Tasks</h2>
          </div>
          <div class="card-body">
            <div v-if="activeTasks.length === 0" class="empty">No active tasks</div>
            <div v-for="t in activeTasks" :key="t.id" class="task-item">
              <span class="priority-dot" :style="{ background: priorityColor(t.priority) }"></span>
              <div class="task-info">
                <span class="task-title">{{ t.title }}</span>
                <span class="task-meta">
                  <span class="badge" :class="t.status">{{ t.status?.replace('_', ' ') }}</span>
                  <span v-if="t.assignee" class="assignee">{{ t.assignee }}</span>
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Activity -->
        <div class="card">
          <div class="card-header">
            <h2>⚡ Recent Activity</h2>
          </div>
          <div class="card-body">
            <div v-if="recentActivity.length === 0" class="empty">No recent activity</div>
            <div v-for="a in recentActivity" :key="a.id" class="activity-item">
              <span class="activity-icon">{{ typeIcon(a.type) }}</span>
              <div class="activity-info">
                <span class="activity-title">{{ a.description || a.title || 'Activity' }}</span>
                <span class="activity-time">{{ timeAgo(a.created_at) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Latest Journal -->
        <div class="card">
          <div class="card-header">
            <h2>📓 Latest Journal Entry</h2>
          </div>
          <div class="card-body">
            <div v-if="!latestJournal" class="empty">No journal entries yet</div>
            <div v-else class="journal-entry">
              <h3>{{ latestJournal.title }}</h3>
              <blockquote>{{ latestJournal.description || latestJournal.content || '' }}</blockquote>
              <span class="journal-date">{{ timeAgo(latestJournal.created_at) }}</span>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
          <div class="card-header">
            <h2>🚀 Quick Actions</h2>
          </div>
          <div class="card-body actions">
            <a href="/tasks" class="action-btn primary">
              <span>＋</span> New Task
            </a>
            <a href="/journal" class="action-btn secondary">
              <span>✍️</span> New Journal Entry
            </a>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.dashboard-root {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem 1.5rem;
  opacity: 0;
  transform: translateY(12px);
  transition: opacity 0.5s ease, transform 0.5s ease;
}
.dashboard-root.loaded {
  opacity: 1;
  transform: translateY(0);
}

.welcome h1 {
  font-size: 1.75rem;
  font-weight: 700;
  color: #e2e8f0;
  margin: 0;
}
.date {
  color: #94a3b8;
  margin: 0.25rem 0 1.5rem;
}

.stats-row {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
  margin-bottom: 1.5rem;
}
@media (max-width: 768px) {
  .stats-row { grid-template-columns: repeat(2, 1fr); }
}

.stat-card {
  background: #192d33;
  border: 1px solid #233f48;
  border-radius: 12px;
  padding: 1.25rem;
  display: flex;
  align-items: center;
  gap: 1rem;
}
.stat-icon { font-size: 1.75rem; }
.stat-info { display: flex; flex-direction: column; }
.stat-value { font-size: 1.5rem; font-weight: 700; }
.stat-label { font-size: 0.8rem; color: #94a3b8; margin-top: 2px; }

.grid-2col {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.25rem;
}
@media (max-width: 768px) {
  .grid-2col { grid-template-columns: 1fr; }
}

.card {
  background: #192d33;
  border: 1px solid #233f48;
  border-radius: 12px;
  overflow: hidden;
}
.card-header {
  padding: 1rem 1.25rem 0.5rem;
  border-bottom: 1px solid #233f48;
}
.card-header h2 {
  margin: 0;
  font-size: 1rem;
  font-weight: 600;
  color: #e2e8f0;
}
.card-body {
  padding: 1rem 1.25rem;
}
.empty {
  color: #64748b;
  font-size: 0.875rem;
  text-align: center;
  padding: 1rem 0;
}

/* Tasks */
.task-item {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 0.6rem 0;
  border-bottom: 1px solid #233f4830;
}
.task-item:last-child { border-bottom: none; }
.priority-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  margin-top: 6px;
  flex-shrink: 0;
}
.task-info { display: flex; flex-direction: column; gap: 4px; min-width: 0; }
.task-title { color: #e2e8f0; font-size: 0.875rem; font-weight: 500; }
.task-meta { display: flex; gap: 0.5rem; align-items: center; }
.badge {
  font-size: 0.7rem;
  padding: 1px 8px;
  border-radius: 9999px;
  text-transform: capitalize;
  font-weight: 500;
}
.badge.in_progress { background: #f59e0b22; color: #f59e0b; }
.badge.todo { background: #13b6ec22; color: #13b6ec; }
.assignee { font-size: 0.75rem; color: #64748b; }

/* Activity */
.activity-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem 0;
  border-bottom: 1px solid #233f4830;
}
.activity-item:last-child { border-bottom: none; }
.activity-icon { font-size: 1.1rem; flex-shrink: 0; }
.activity-info { display: flex; justify-content: space-between; width: 100%; min-width: 0; }
.activity-title {
  color: #cbd5e1;
  font-size: 0.85rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  flex: 1;
}
.activity-time { color: #64748b; font-size: 0.75rem; flex-shrink: 0; margin-left: 0.5rem; }

/* Journal */
.journal-entry h3 {
  margin: 0 0 0.75rem;
  color: #e2e8f0;
  font-size: 1rem;
  font-weight: 600;
}
blockquote {
  margin: 0;
  padding: 0.75rem 1rem;
  border-left: 3px solid #13b6ec;
  background: #13b6ec08;
  color: #94a3b8;
  font-size: 0.875rem;
  line-height: 1.6;
  border-radius: 0 8px 8px 0;
  max-height: 120px;
  overflow: hidden;
}
.journal-date { color: #64748b; font-size: 0.75rem; margin-top: 0.5rem; display: block; }

/* Actions */
.actions {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}
.action-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.75rem 1.25rem;
  border-radius: 10px;
  font-weight: 600;
  font-size: 0.9rem;
  text-decoration: none;
  transition: transform 0.15s, opacity 0.15s;
  cursor: pointer;
}
.action-btn:hover { transform: translateY(-1px); opacity: 0.9; }
.action-btn.primary { background: #13b6ec; color: #fff; }
.action-btn.secondary { background: #233f48; color: #e2e8f0; border: 1px solid #2d5460; }
</style>
