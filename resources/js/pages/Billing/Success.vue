<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

interface Props {
    order_id?: string;
    plan_name?: string;
}

withDefaults(defineProps<Props>(), {
    plan_name: 'выбранный тариф',
});

const isVisible = ref(false);

onMounted(() => {
    setTimeout(() => {
        isVisible.value = true;
    }, 100);
});
</script>

<template>
    <Head title="Платеж успешен" />

    <div class="min-h-screen bg-gradient-to-br from-green-50 to-emerald-50 dark:from-slate-950 dark:to-slate-900 flex items-center justify-center px-4 py-12">
        <div class="max-w-md w-full">
            <div :class="['mb-8 flex justify-center transform transition-all duration-700', isVisible ? 'scale-100 opacity-100' : 'scale-0 opacity-0']">
                <div class="relative">
                    <div class="absolute inset-0 rounded-full border-4 border-green-200 dark:border-green-900 animate-pulse"></div>
                    <div class="relative w-24 h-24 rounded-full bg-green-100 dark:bg-green-900 flex items-center justify-center border-4 border-green-500">
                        <svg class="w-12 h-12 text-green-600 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div :class="['text-center transition-all duration-700 delay-300', isVisible ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0']">
                <h1 class="text-4xl font-bold text-slate-900 dark:text-white mb-4">
                    Оплата прошла успешно!
                </h1>

                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6 mb-8">
                    <p class="text-lg text-slate-600 dark:text-slate-300 mb-4">
                        Ваш баланс обновлен
                    </p>
                    <p v-if="plan_name" class="text-base font-semibold text-blue-600 dark:text-blue-400 mb-2">
                        Тариф "{{ plan_name }}" активирован
                    </p>
                    <p class="text-base text-slate-500 dark:text-slate-400">
                        Все функции готовы к использованию
                    </p>
                </div>

                <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl p-6 mb-8">
                    <div class="space-y-3 text-sm text-slate-600 dark:text-slate-300">
                        <div class="flex items-center justify-center gap-3">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            <span>Доступ активирован</span>
                        </div>
                        <div class="flex items-center justify-center gap-3">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            <span>Платеж подтвержден</span>
                        </div>
                        <div class="flex items-center justify-center gap-3">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            <span>Спасибо за заказ!</span>
                        </div>
                    </div>
                </div>

                <Link
                    href="/dashboard"
                    class="inline-flex items-center justify-center gap-2 bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 text-white font-semibold py-3 px-8 rounded-lg transition-all duration-300 hover:shadow-lg hover:-translate-y-1 w-full"
                >
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L9 4.414V16a1 1 0 102 0V4.414l6.293 6.293a1 1 0 001.414-1.414l-7-7z" />
                    </svg>
                    <span>Перейти в личный кабинет</span>
                </Link>
            </div>

            <div :class="['mt-8 text-center transition-all duration-700 delay-500', isVisible ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0']">
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Квитанция отправлена на вашу электронную почту
                </p>
            </div>
        </div>
    </div>
</template>
