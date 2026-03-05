<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';

const agents = ref([
  {
    id: 'sandi',
    name: 'Sandi',
    emoji: '👨‍💼',
    role: 'Owner & Operator',
    type: 'human',
    model: null,
    channel: 'All channels',
    responsibilities: ['Business strategy', 'Final decisions', 'Product direction'],
    talks_to: ['alex', 'brandon', 'sarah'],
    color: '#13b6ec',
    level: 0,
  },
  {
    id: 'alex',
    name: 'Alex',
    emoji: '🤙',
    role: 'Lead Agent — Strategy, Planning & Orchestration',
    type: 'ai',
    model: 'Claude Opus',
    channel: 'Telegram (main)',
    responsibilities: ['Strategic planning', 'Architecture decisions', 'Memory & context', 'Agent delegation', 'Spawns Rex for coding'],
    talks_to: ['sandi', 'brandon', 'sarah', 'rex', 'luna'],
    color: '#8b5cf6',
    level: 1,
  },
  {
    id: 'rex',
    name: 'Rex',
    emoji: '🔧',
    role: 'Developer — Builds, Deploys, Reports',
    type: 'ai',
    model: 'Claude Sonnet',
    channel: 'Sub-agent (no Telegram)',
    responsibilities: ['Feature development', 'Bug fixes', 'Git commits & deployments', 'Code review execution'],
    talks_to: ['alex'],
    color: '#f59e0b',
    level: 2,
  },
  {
    id: 'brandon',
    name: 'Brandon',
    emoji: '🎨',
    role: 'Brandy Platform — LPs, Markets, Analytics',
    type: 'ai',
    model: 'Claude Sonnet',
    channel: 'Telegram (brandy)',
    responsibilities: ['Landing page management', 'Market deployments', 'Brandy order stats', 'Campaign analytics', 'Spawns Rex for Brandy coding'],
    talks_to: ['sandi', 'alex'],
    color: '#ec4899',
    level: 2,
  },
  {
    id: 'sarah',
    name: 'Sarah',
    emoji: '📦',
    role: 'Sansibe Ops — Orders, Shipping, Support',
    type: 'ai',
    model: 'Claude Sonnet',
    channel: 'Telegram (sansibe)',
    responsibilities: ['WooCommerce order management', 'Shipping labels & couriers', 'Customer support tickets', 'Customer emails & SMS'],
    talks_to: ['sandi', 'alex'],
    color: '#22c55e',
    level: 2,
  },
  {
    id: 'luna',
    name: 'Luna',
    emoji: '✍️',
    role: 'Copy Team Leader — Product & LP Copywriting',
    type: 'ai',
    model: 'Claude Opus',
    channel: 'Sub-agent (no Telegram)',
    responsibilities: ['Product copy', 'Landing page copy', 'Coordinates GPT-4o + Gemini', 'Multi-model copy comparison', 'Final copy decisions'],
    workflow: [
      '1. Write SI master copy first (subtitle, description, benefits, ingredients, usage)',
      '2. Run Gemini Batch API → localize to all other markets in one job (50% cheaper, ~5 min)',
      '3. Review outputs for quality across markets',
      '4. Hand off to Alex/Brandon to push to Brandy DB',
    ],
    talks_to: ['alex', 'brandon'],
    color: '#06b6d4',
    level: 2,
  },
  {
    id: 'gpt4o',
    name: 'GPT-4o',
    emoji: '🟢',
    role: 'Copy Team — Headlines & Hooks',
    type: 'ai',
    model: 'GPT-4o (OpenAI)',
    channel: 'Sub-agent (Luna only)',
    responsibilities: ['Headline variations', 'Hook writing', 'CTA copy', 'Conversion-focused rewrites'],
    talks_to: ['luna'],
    color: '#10b981',
    level: 3,
  },
  {
    id: 'gemini',
    name: 'Gemini',
    emoji: '💎',
    role: 'Copy Team — Localization & Market Nuance',
    type: 'ai',
    model: 'Gemini 2.0 Flash (Google)',
    channel: 'Sub-agent (Luna only)',
    responsibilities: ['Market localization', 'Non-English copy', 'Cultural nuance', 'Multi-language variations'],
    talks_to: ['luna'],
    color: '#3b82f6',
    level: 3,
  },
]);

const commFlows = [
  { from: 'Sandi', to: 'Alex', emoji: '👨‍💼 → 🤙', type: 'bidirectional', desc: 'Strategy, requests, decisions' },
  { from: 'Alex', to: 'Rex', emoji: '🤙 → 🔧', type: 'delegate', desc: 'Spawns for coding tasks' },
  { from: 'Brandon', to: 'Alex', emoji: '🎨 → 🤙', type: 'escalate', desc: 'Escalates issues & blockers' },
  { from: 'Brandon', to: 'Rex', emoji: '🎨 → 🔧', type: 'delegate', desc: 'Spawns for Brandy coding tasks' },
  { from: 'Sarah', to: 'Alex', emoji: '📦 → 🤙', type: 'escalate', desc: 'Escalates issues & blockers' },
  { from: 'Sandi', to: 'Brandon', emoji: '👨‍💼 → 🎨', type: 'bidirectional', desc: 'Brandy ops via Telegram' },
  { from: 'Sandi', to: 'Sarah', emoji: '👨‍💼 → 📦', type: 'bidirectional', desc: 'Sansibe ops via Telegram' },
  { from: 'Alex', to: 'Luna', emoji: '🤙 → ✍️', type: 'delegate', desc: 'Spawns for copy tasks' },
  { from: 'Brandon', to: 'Luna', emoji: '🎨 → ✍️', type: 'delegate', desc: 'Spawns for LP/product copy' },
  { from: 'Luna', to: 'GPT-4o', emoji: '✍️ → 🟢', type: 'delegate', desc: 'Spawns for headline variants' },
  { from: 'Luna', to: 'Gemini', emoji: '✍️ → 💎', type: 'delegate', desc: 'Spawns for localization' },
];

const topLevel = computed(() => agents.value.filter(a => a.level === 0));
const midLevel = computed(() => agents.value.filter(a => a.level === 1));
const bottomLevel = computed(() => agents.value.filter(a => a.level === 2));

function getAgent(id) {
  return agents.value.find(a => a.id === id);
}

function flowColor(type) {
  return { bidirectional: '#13b6ec', delegate: '#f59e0b', escalate: '#8b5cf6' }[type] || '#6b7280';
}

function flowLabel(type) {
  return { bidirectional: '↔ Bidirectional', delegate: '→ Delegates', escalate: '→ Escalates' }[type] || '';
}
</script>

<template>
  <AppLayout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-10">
        <h1 class="text-3xl font-bold tracking-tight">🏢 Organisation</h1>
        <p class="mt-2 text-gray-500 dark:text-gray-400">Team structure, roles, and communication flows</p>
      </div>

      <!-- Org Chart -->
      <div class="flex flex-col items-center gap-2 mb-12">
        <!-- Top: Sandi -->
        <div class="flex justify-center">
          <div
            v-for="agent in topLevel"
            :key="agent.id"
            class="relative w-72 rounded-2xl border-2 p-5 transition-all hover:scale-[1.02] hover:shadow-xl"
            :style="{ borderColor: agent.color, background: agent.color + '08' }"
            :class="'dark:bg-dark-card bg-white shadow-lg'"
          >
            <div class="flex items-center gap-3 mb-3">
              <span class="text-3xl">{{ agent.emoji }}</span>
              <div>
                <div class="font-bold text-lg">{{ agent.name }}</div>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" :style="{ background: agent.color + '20', color: agent.color }">
                  {{ agent.type === 'human' ? '👤 Human' : '🤖 AI Agent' }}
                </span>
              </div>
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400 mb-3">{{ agent.role }}</div>
            <div class="text-xs text-gray-400 dark:text-gray-500 mb-2">📡 {{ agent.channel }}</div>
            <ul class="space-y-1">
              <li v-for="r in agent.responsibilities" :key="r" class="text-xs text-gray-600 dark:text-gray-300 flex items-start gap-1.5">
                <span class="mt-0.5 text-[8px]" :style="{ color: agent.color }">●</span>
                {{ r }}
              </li>
            </ul>
          </div>
        </div>

        <!-- Connector line -->
        <div class="w-px h-8 bg-gray-300 dark:bg-gray-600"></div>

        <!-- Mid: Alex -->
        <div class="flex justify-center">
          <div
            v-for="agent in midLevel"
            :key="agent.id"
            class="relative w-72 rounded-2xl border-2 p-5 transition-all hover:scale-[1.02] hover:shadow-xl"
            :style="{ borderColor: agent.color, background: agent.color + '08' }"
            :class="'dark:bg-dark-card bg-white shadow-lg'"
          >
            <div class="flex items-center gap-3 mb-3">
              <span class="text-3xl">{{ agent.emoji }}</span>
              <div>
                <div class="font-bold text-lg">{{ agent.name }}</div>
                <div class="flex items-center gap-2">
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" :style="{ background: agent.color + '20', color: agent.color }">
                    🤖 AI Agent
                  </span>
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-purple-500/20 text-purple-400 border border-purple-500/30">
                    {{ agent.model }}
                  </span>
                </div>
              </div>
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400 mb-3">{{ agent.role }}</div>
            <div class="text-xs text-gray-400 dark:text-gray-500 mb-2">📡 {{ agent.channel }}</div>
            <ul class="space-y-1">
              <li v-for="r in agent.responsibilities" :key="r" class="text-xs text-gray-600 dark:text-gray-300 flex items-start gap-1.5">
                <span class="mt-0.5 text-[8px]" :style="{ color: agent.color }">●</span>
                {{ r }}
              </li>
            </ul>
            <div v-if="agent.workflow" class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
              <div class="text-[10px] uppercase tracking-wider text-gray-400 mb-2">⚙️ Workflow</div>
              <ol class="space-y-1.5">
                <li v-for="step in agent.workflow" :key="step" class="text-xs text-gray-600 dark:text-gray-300 flex items-start gap-1.5">
                  <span class="mt-0.5 shrink-0 font-bold" :style="{ color: agent.color }">›</span>
                  {{ step }}
                </li>
              </ol>
            </div>
            <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
              <div class="text-[10px] uppercase tracking-wider text-gray-400 mb-1">Communicates with</div>
              <div class="flex flex-wrap gap-1.5">
                <span v-for="tid in agent.talks_to" :key="tid" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
                  {{ getAgent(tid)?.emoji }} {{ getAgent(tid)?.name }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Connector lines (branching) -->
        <div class="flex items-start justify-center w-full max-w-3xl">
          <div class="flex-1 border-t-2 border-gray-300 dark:border-gray-600 mt-0"></div>
          <div class="w-px h-0"></div>
          <div class="flex-1 border-t-2 border-gray-300 dark:border-gray-600 mt-0"></div>
        </div>
        <div class="flex justify-center gap-8 w-full max-w-3xl -mt-px">
          <div class="flex-1 flex justify-center"><div class="w-px h-8 bg-gray-300 dark:bg-gray-600"></div></div>
          <div class="flex-1 flex justify-center"><div class="w-px h-8 bg-gray-300 dark:bg-gray-600"></div></div>
          <div class="flex-1 flex justify-center"><div class="w-px h-8 bg-gray-300 dark:bg-gray-600"></div></div>
        </div>

        <!-- Bottom: Rex, Brandon, Sarah -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 w-full max-w-5xl">
          <div
            v-for="agent in bottomLevel"
            :key="agent.id"
            class="rounded-2xl border-2 p-5 transition-all hover:scale-[1.02] hover:shadow-xl"
            :style="{ borderColor: agent.color, background: agent.color + '08' }"
            :class="'dark:bg-dark-card bg-white shadow-lg'"
          >
            <div class="flex items-center gap-3 mb-3">
              <span class="text-3xl">{{ agent.emoji }}</span>
              <div>
                <div class="font-bold text-lg">{{ agent.name }}</div>
                <div class="flex items-center gap-2">
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" :style="{ background: agent.color + '20', color: agent.color }">
                    🤖 AI Agent
                  </span>
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-500/20 text-amber-400 border border-amber-500/30">
                    {{ agent.model }}
                  </span>
                </div>
              </div>
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400 mb-3">{{ agent.role }}</div>
            <div class="text-xs text-gray-400 dark:text-gray-500 mb-2">📡 {{ agent.channel }}</div>
            <ul class="space-y-1">
              <li v-for="r in agent.responsibilities" :key="r" class="text-xs text-gray-600 dark:text-gray-300 flex items-start gap-1.5">
                <span class="mt-0.5 text-[8px]" :style="{ color: agent.color }">●</span>
                {{ r }}
              </li>
            </ul>
            <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
              <div class="text-[10px] uppercase tracking-wider text-gray-400 mb-1">Communicates with</div>
              <div class="flex flex-wrap gap-1.5">
                <span v-for="tid in agent.talks_to" :key="tid" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
                  {{ getAgent(tid)?.emoji }} {{ getAgent(tid)?.name }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Communication Flows -->
      <div class="rounded-2xl border dark:border-dark-border bg-white dark:bg-dark-card p-6 shadow-lg">
        <h2 class="text-xl font-bold mb-6 text-gray-900 dark:text-white">🔗 Communication Flows</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <div
            v-for="flow in commFlows"
            :key="flow.from + flow.to"
            class="flex items-center gap-4 rounded-xl border p-4 transition-colors bg-gray-50 dark:bg-dark-bg/60"
            :style="{ borderColor: flowColor(flow.type) + '40' }"
            :class="'dark:bg-dark-bg/50 bg-gray-50 hover:bg-gray-100 dark:hover:bg-white/5'"
          >
            <span class="text-2xl flex-shrink-0">{{ flow.emoji }}</span>
            <div>
              <div class="font-semibold text-sm text-gray-900 dark:text-white">{{ flow.from }} → {{ flow.to }}</div>
              <div class="text-xs text-gray-500 dark:text-gray-400">{{ flow.desc }}</div>
              <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded-full text-[10px] font-medium" :style="{ background: flowColor(flow.type) + '20', color: flowColor(flow.type) }">
                {{ flowLabel(flow.type) }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
