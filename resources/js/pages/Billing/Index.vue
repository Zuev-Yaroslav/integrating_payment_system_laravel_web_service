<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import TestCardInfo from '@/components/TestCardInfo.vue';
import { route } from 'ziggy-js';

interface Plan {
    id: string;
    name: string;
    price: number;
    currency: string;
    features: string[];
    recommended?: boolean;
}

interface Props {
    plans: Plan[];
}

defineProps<Props>();

const loadingPlanId = ref<string | null>(null);
const isSandbox = true; // режим тестирования YooKassa

const handleSelectPlan = async (planId: string) => {
    loadingPlanId.value = planId;

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const response = await fetch('/billing/initiate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ plan_id: planId }),
        });

        if (!response.ok) {
            throw new Error('Failed to initiate payment');
        }

        const data = await response.json();

        // Редирект на YooKassa
        if (data.redirect_url) {
            window.location.href = data.redirect_url;
        }
    } catch (error) {
        console.error('Payment initiation error:', error);
        loadingPlanId.value = null;
    }
};

const isLoading = computed(() => loadingPlanId.value !== null);
</script>

<template>
    <Head title="Выбор тарифа" />

    <div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-950 dark:to-slate-900 py-12 px-4 sm:px-6 lg:px-8">
        <!-- Sandbox Alert -->
        <div v-if="isSandbox" class="mb-8 max-w-6xl mx-auto">
            <div class="flex items-start gap-3 bg-amber-500 text-white p-4 rounded-lg shadow-lg">
                <div class="flex-shrink-0 mt-0.5">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="font-semibold text-sm">Режим тестирования ЮKassa</h3>
                    <p class="text-sm mt-1 opacity-95">Карты для симуляции успеха и ошибок указаны внизу страницы</p>
                </div>
            </div>
        </div>
        <!-- Main Content -->
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-12">
                <h1 class="text-4xl sm:text-5xl font-bold text-slate-900 dark:text-white mb-4">
                    Выберите подходящий тариф
                </h1>
                <p class="text-lg text-slate-600 dark:text-slate-400">
                    Масштабируемые решения для любого размера бизнеса
                </p>
            </div>

            <!-- Plans Grid -->
            <div class="grid md:grid-cols-3 gap-8 mb-16">
                <div
                    v-for="plan in plans"
                    :key="plan.id"
                    :class="[
                        'relative rounded-2xl shadow-lg overflow-hidden transition-all duration-300 transform hover:shadow-2xl hover:-translate-y-1',
                        plan.recommended
                            ? 'md:scale-105 bg-gradient-to-br from-blue-600 to-blue-700 text-white'
                            : 'bg-white dark:bg-slate-800 text-slate-900 dark:text-white',
                    ]"
                >
                    <!-- Recommended Badge -->
                    <div v-if="plan.recommended" class="absolute top-0 right-0 bg-yellow-400 text-slate-900 px-4 py-1 text-sm font-semibold rounded-bl-lg">
                        Популярный
                    </div>

                    <!-- Plan Content -->
                    <div class="p-8">
                        <!-- Plan Name -->
                        <h3 class="text-2xl font-bold mb-2">{{ plan.name }}</h3>

                        <!-- Price -->
                        <div class="mb-6">
                            <div class="flex items-baseline gap-2">
                                <span class="text-5xl font-bold">{{ plan.price }}</span>
                                <span class="text-lg opacity-80">{{ plan.currency }}</span>
                            </div>
                            <p class="text-sm opacity-75 mt-1">в месяц</p>
                        </div>

                        <!-- CTA Button -->
                        <button
                            @click="handleSelectPlan(plan.id)"
                            :disabled="isLoading"
                            :class="[
                                'w-full py-3 px-6 rounded-lg font-semibold mb-8 transition-all duration-300 disabled:opacity-75 disabled:cursor-not-allowed',
                                plan.recommended
                                    ? 'bg-white text-blue-600 hover:bg-blue-50 disabled:hover:bg-white'
                                    : 'bg-blue-600 text-white hover:bg-blue-700 disabled:hover:bg-blue-600 dark:bg-blue-500 dark:hover:bg-blue-600',
                                loadingPlanId === plan.id && 'opacity-75 cursor-wait',
                            ]"
                        >
                            <div class="flex items-center justify-center gap-2">
                                <svg
                                    v-if="loadingPlanId === plan.id"
                                    class="w-5 h-5 animate-spin"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>{{ loadingPlanId === plan.id ? 'Загрузка...' : 'Выбрать' }}</span>
                            </div>
                        </button>

                        <!-- Features List -->
                        <div class="space-y-4">
                            <p class="text-xs font-semibold opacity-75 uppercase tracking-wider">Возможности:</p>
                            <ul class="space-y-3">
                                <li v-for="(feature, index) in plan.features" :key="index" class="flex items-start gap-3">
                                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" :class="[plan.recommended ? 'text-yellow-300' : 'text-green-500']" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="text-sm">{{ feature }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Test Cards Info -->
            <TestCardInfo />
        </div>
    </div>
</template>
