<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, onMounted, computed } from 'vue';

const routines = ref([]);
const loading = ref(true);
const error = ref(null);

async function fetchRoutines() {
  try {
    const res = await fetch('/api/scheduled-routines', { credentials: 'same-origin' });
    const json = await res.json();
    if (json.success) routines.value = json.data;
  } catch (e) {
    error.value = 'Failed to load routines';
  } finally {
    loading.value = false;
  }
}

onMounted(fetchRoutines);

function cronToHuman(cron) {
  if (!cron) return 'No schedule';
  const presets = {
    '* * * * *': 'Every minute',
    '*/5 * * * *': 'Every 5 minutes',
    '*/10 * * * *': 'Every 10 minutes',
    '*/15 * * * *': 'Every 15 minutes',
    '*/30 * * * *': 'Every 30 minutes',
    '0 * * * *': 'Every hour',
    '0 */2 * * *': 'Every 2 hours',
    '0 */4 * * *': 'Every 4 hours',
    '0 */6 * * *': 'Every 6 hours',
    '0 */12 * * *': 'Every 12 hours',
    '0 0 * * *': 'Daily at midnight',
    '0 6 * * *': 'Daily at 6:00',
    '0 8 * * *': 'Daily at 8:00',
    '0 9 * * *': 'Daily at 9:00',
    '0 12 * * *': 'Daily at noon',
    '0 0 * * 0': 'Weekly on Sunday',
    '0 0 * * 1': 'Weekly on Monday',
    '0 0 1 * *': 'Monthly on the 1st',
  };
  if (presets[cron]) return presets[cron];
  const parts = cron.split(' ');
  if (parts.length !== 5) return cron;
  const [min, hour, dom, mon, dow] = parts;
  if (min.startsWith('*/')) return `Every ${min.slice(2)} minutes`;
  if (hour.startsWith('*/') && min === '0') return `Every ${hour.slice(2)} hours`;
  if (hour !== '*' && min !== '*' && dom === '*' && mon === '*' && dow === '*')
    return `Daily at ${hour.padStart(2,'0')}:${min.padStart(2,'0')}`;
  return cron;
}

function relativeTime(dateStr) {
  if (!dateStr) return 'Never';
  const now = Date.now();
  const then = new Date(dateStr).getTime();
  const diff = now - then;
  const abs = Math.abs(diff);
  const future = diff < 0;
  const mins = Math.floor(abs / 60000);
  const hours = Math.floor(abs / 3600000);
  const days = Math.floor(abs / 86400000);
  let label;
  if (mins < 1) label = 'just now';
  else if (mins < 60) label = `${mins}m`;
  else if (hours < 24) label = `${hours}h`;
  else label = `${days}d`;
  if (label === 'just now') return label;
  return future ? `in ${label}` : `${label} ago`;
}

function stripColor(r) {
  if (r.last_status === 'failure') return 'bg-red-500';
  if (r.is_active) return 'bg-green-500';
  return 'bg-gray-500';
}

async function toggleActive(routine) {
  routine.is_active = !routine.is_active;
}
</script>

<template>
  <AppLayout>
    <div class="p-6 max-w-6xl mx-auto">
      <h1 class="text-2xl font-bold text-white mb-6">Routines</h1>

      <!-- Loading -->
      <div v-if="loading" class="text-gray-400 text-center py-20">Loading routines…</div>

      <!-- Error -->
      <div v-else-if="error" class="text-red-400 text-center py-20">{{ error }}</div>

      <!-- Empty -->
      <div v-else-if="routines.length === 0" class="text-center py-20">
        <div class="text-5xl mb-4">⏰</div>
        <p class="text-gray-400 text-lg">No routines yet</p>
        <p class="text-gray-600 text-sm mt-1">Scheduled routines will appear here</p>
      </div>

      <!-- Grid -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div
          v-for="r in routines"
          :key="r.id"
          class="relative flex rounded-lg border overflow-hidden"
          :class="[
            'bg-[#192d33] border-[#233f48]',
            r.is_active && r.last_status !== 'failure' ? 'ring-1 ring-green-500/20' : ''
          ]"
        >
          <!-- Status strip -->
          <div class="w-1 shrink-0 relative">
            <div :class="[stripColor(r), 'w-full h-full']"></div>
            <div
              v-if="r.is_active && r.last_status !== 'failure'"
              :class="[stripColor(r), 'absolute inset-0 animate-pulse opacity-60']"
            ></div>
          </div>

          <div class="flex-1 p-4 min-w-0">
            <!-- Header -->
            <div class="flex items-start justify-between gap-3 mb-2">
              <div class="min-w-0">
                <h3 class="text-white font-semibold truncate">{{ r.name }}</h3>
                <p v-if="r.description" class="text-gray-400 text-sm mt-0.5 line-clamp-2">{{ r.description }}</p>
              </div>

              <!-- Toggle -->
              <button
                @click="toggleActive(r)"
                class="shrink-0 relative w-10 h-5 rounded-full transition-colors duration-200 focus:outline-none"
                :class="r.is_active ? 'bg-green-500' : 'bg-gray-600'"
              >
                <span
                  class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full transition-transform duration-200"
                  :class="r.is_active ? 'translate-x-5' : 'translate-x-0'"
                ></span>
              </button>
            </div>

            <!-- Schedule -->
            <div class="flex items-center gap-1.5 text-xs text-gray-400 mb-3">
              <span>🕐</span>
              <span>{{ cronToHuman(r.schedule) }}</span>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-between text-xs">
              <div class="flex items-center gap-3">
                <!-- Last run -->
                <span class="text-gray-500">
                  Last: {{ relativeTime(r.last_run_at) }}
                  <span v-if="r.last_status === 'success'" class="ml-0.5">✅</span>
                  <span v-else-if="r.last_status === 'failure'" class="ml-0.5">❌</span>
                </span>
                <!-- Next run -->
                <span v-if="r.next_run_at && r.is_active" class="text-gray-500">
                  Next: {{ relativeTime(r.next_run_at) }}
                </span>
              </div>
              <!-- Run count -->
              <span class="text-gray-600">{{ r.run_count ?? 0 }} runs</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
