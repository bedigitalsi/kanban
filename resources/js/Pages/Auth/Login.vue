<script setup>
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    email: '',
    password: '',
});

function submit() {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-dark-bg">
        <div class="w-full max-w-sm">
            <h1 class="text-2xl font-bold text-primary text-center mb-8">Mission Control</h1>
            <form @submit.prevent="submit" class="bg-dark-card rounded-xl p-8 border border-dark-border space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Email</label>
                    <input
                        v-model="form.email"
                        type="email"
                        required
                        autofocus
                        class="w-full rounded-lg border border-dark-border bg-dark-bg px-3 py-2 text-gray-100 focus:border-primary focus:ring-primary"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Password</label>
                    <input
                        v-model="form.password"
                        type="password"
                        required
                        class="w-full rounded-lg border border-dark-border bg-dark-bg px-3 py-2 text-gray-100 focus:border-primary focus:ring-primary"
                    />
                </div>
                <div v-if="form.errors.email" class="text-red-400 text-sm">{{ form.errors.email }}</div>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary-dark transition-colors disabled:opacity-50"
                >
                    Sign in
                </button>
            </form>
        </div>
    </div>
</template>
