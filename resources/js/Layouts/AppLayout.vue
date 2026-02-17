<script setup>
import { ref, onMounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';

const page = usePage();
const sidebarOpen = ref(false);
const darkMode = ref(false);
const userMenuOpen = ref(false);

const navItems = [
    { name: 'Dashboard', href: '/', icon: 'space_dashboard' },
    { name: 'Tasks', href: '/tasks', icon: 'task_alt' },
    { name: 'Journal', href: '/journal', icon: 'menu_book' },
    { name: 'Activity', href: '/activity', icon: 'timeline' },
    { name: 'Brain', href: '/brain', icon: 'psychology' },
    { name: 'Routines', href: '/routines', icon: 'schedule' },
    { name: 'Projects', href: '/projects', icon: 'folder' },
];

onMounted(() => {
    darkMode.value = localStorage.getItem('darkMode') === 'true';
    applyDarkMode();
});

function toggleDarkMode() {
    darkMode.value = !darkMode.value;
    localStorage.setItem('darkMode', darkMode.value);
    applyDarkMode();
}

function applyDarkMode() {
    document.documentElement.classList.toggle('dark', darkMode.value);
}

function logout() {
    router.post('/logout');
}

function isActive(href) {
    return page.url.startsWith(href);
}
</script>

<template>
    <div class="min-h-screen" :class="darkMode ? 'bg-dark-bg text-gray-100' : 'bg-light-bg text-gray-900'">
        <!-- Mobile sidebar backdrop -->
        <div v-if="sidebarOpen" class="fixed inset-0 z-40 bg-black/50 lg:hidden" @click="sidebarOpen = false"></div>

        <!-- Sidebar -->
        <aside
            class="fixed inset-y-0 left-0 z-50 w-64 transform transition-transform duration-200 lg:translate-x-0"
            :class="[
                sidebarOpen ? 'translate-x-0' : '-translate-x-full',
                darkMode ? 'bg-dark-card border-r border-dark-border' : 'bg-white border-r border-gray-200'
            ]"
        >
            <div class="flex h-16 items-center px-6">
                <span class="text-xl font-bold text-primary">Mission Control</span>
            </div>
            <nav class="mt-2 px-3 space-y-1">
                <Link
                    v-for="item in navItems"
                    :key="item.name"
                    :href="item.href"
                    class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors"
                    :class="isActive(item.href)
                        ? 'bg-primary/10 text-primary'
                        : darkMode ? 'text-gray-400 hover:bg-white/5 hover:text-gray-200' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'"
                    @click="sidebarOpen = false"
                >
                    <span class="material-symbols-rounded text-[20px]">{{ item.icon }}</span>
                    {{ item.name }}
                </Link>
            </nav>
        </aside>

        <!-- Main content -->
        <div class="lg:pl-64">
            <!-- Top bar -->
            <header
                class="sticky top-0 z-30 flex h-16 items-center justify-between px-4 lg:px-8"
                :class="darkMode ? 'bg-dark-card/80 backdrop-blur border-b border-dark-border' : 'bg-white/80 backdrop-blur border-b border-gray-200'"
            >
                <button class="lg:hidden p-2 rounded-lg" @click="sidebarOpen = true">
                    <span class="material-symbols-rounded">menu</span>
                </button>
                <div class="flex-1"></div>
                <div class="flex items-center gap-2">
                    <!-- Dark mode toggle -->
                    <button @click="toggleDarkMode" class="p-2 rounded-lg transition-colors" :class="darkMode ? 'hover:bg-white/10' : 'hover:bg-gray-100'">
                        <span class="material-symbols-rounded text-[20px]">{{ darkMode ? 'light_mode' : 'dark_mode' }}</span>
                    </button>
                    <!-- User menu -->
                    <div class="relative">
                        <button @click="userMenuOpen = !userMenuOpen" class="flex items-center gap-2 p-2 rounded-lg transition-colors" :class="darkMode ? 'hover:bg-white/10' : 'hover:bg-gray-100'">
                            <span class="material-symbols-rounded text-[20px]">person</span>
                            <span class="text-sm font-medium">{{ page.props.auth?.user?.name || 'User' }}</span>
                            <span class="material-symbols-rounded text-[16px]">expand_more</span>
                        </button>
                        <div
                            v-if="userMenuOpen"
                            class="absolute right-0 mt-2 w-48 rounded-lg shadow-lg py-1 z-50"
                            :class="darkMode ? 'bg-dark-card border border-dark-border' : 'bg-white border border-gray-200'"
                            @click="userMenuOpen = false"
                        >
                            <button @click="logout" class="w-full text-left px-4 py-2 text-sm transition-colors" :class="darkMode ? 'hover:bg-white/10' : 'hover:bg-gray-100'">
                                <span class="material-symbols-rounded text-[16px] mr-2 align-middle">logout</span>
                                Logout
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page content -->
            <main class="p-4 lg:p-8">
                <slot />
            </main>
        </div>
    </div>
</template>
