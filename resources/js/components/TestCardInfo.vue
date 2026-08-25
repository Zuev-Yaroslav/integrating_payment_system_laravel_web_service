<script setup lang="ts">
import { ref } from 'vue';

const isExpanded = ref(false);
</script>

<template>
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg overflow-hidden">
        <!-- Header -->
        <button
            @click="isExpanded = !isExpanded"
            class="w-full px-8 py-6 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
        >
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v4"></path>
                </svg>
                <div class="text-left">
                    <h3 class="font-semibold text-slate-900 dark:text-white">Тестовые карты ЮKassa</h3>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Для симуляции различных сценариев</p>
                </div>
            </div>
            <svg
                :class="['w-5 h-5 text-slate-600 dark:text-slate-400 transition-transform', isExpanded && 'rotate-180']"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 20 20"
                fill="currentColor"
            >
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </button>

        <!-- Content -->
        <transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-2"
        >
            <div v-show="isExpanded" class="px-8 py-6 border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900">
                <div class="space-y-6">
                    <!-- Success Card -->
                    <div>
                        <h4 class="font-semibold text-green-600 dark:text-green-400 mb-3 flex items-center gap-2">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            Успешный платеж
                        </h4>
                        <div class="bg-white dark:bg-slate-800 p-4 rounded-lg space-y-2 text-sm">
                            <p class="text-slate-700 dark:text-slate-300">
                                <span class="font-mono font-semibold">4111 1111 1111 1111</span>
                            </p>
                            <p class="text-slate-600 dark:text-slate-400">Срок: 12/25 | CVC: 123</p>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>
