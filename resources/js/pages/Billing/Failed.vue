<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

interface Props {
    error_message?: string;
}

withDefaults(defineProps<Props>(), {
    error_message: 'Платеж отклонен системой платежей',
});

const isVisible = ref(false);

onMounted(() => {
    setTimeout(() => {
        isVisible.value = true;
    }, 100);
});
</script>

<template>
    <Head title="Ошибка платежа" />

    <div class="min-h-screen bg-gradient-to-br from-red-50 to-rose-50 dark:from-slate-950 dark:to-slate-900 flex items-center justify-center px-4 py-12">
        <div class="max-w-md w-full">
            <div :class="['mb-8 flex justify-center transform transition-all duration-700', isVisible ? 'scale-100 opacity-100' : 'scale-0 opacity-0']">
                <div class="relative">
                    <div class="absolute inset-0 rounded-full border-4 border-red-200 dark:border-red-900 animate-pulse"></div>
                    <div class="relative w-24 h-24 rounded-full bg-red-100 dark:bg-red-900 flex items-center justify-center border-4 border-red-500">
                        <svg class="w-12 h-12 text-red-600 dark:text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div :class="['text-center transition-all duration-700 delay-300', isVisible ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0']">
                <h1 class="text-4xl font-bold text-slate-900 dark:text-white mb-4">
                    Не удалось совершить оплату
                </h1>

                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6 mb-8 border-l-4 border-red-500">
                    <h2 class="text-sm font-semibold text-slate-600 dark:text-slate-300 mb-3 uppercase tracking-wider">
                        Детали ошибки:
                    </h2>
                    <p class="text-lg text-red-600 dark:text-red-400 font-semibold break-words">
                        {{ error_message }}
                    </p>
                </div>

                <div class="bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 rounded-xl p-6 mb-8">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-4">
                        Что можно попробовать:
                    </h3>
                    <ul class="space-y-3 text-sm text-slate-600 dark:text-slate-300">
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            <span>Проверьте данные карты и срок действия</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            <span>Убедитесь, что достаточно средств на счете</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            <span>Попробуйте использовать другой способ оплаты</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            <span>Очистите кэш браузера и попробуйте еще раз</span>
                        </li>
                    </ul>
                </div>

                <div class="space-y-3">
                    <Link
                        href="/billing"
                        class="inline-flex items-center justify-center gap-2 w-full bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 text-white font-semibold py-3 px-8 rounded-lg transition-all duration-300 hover:shadow-lg hover:-translate-y-1"
                    >
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                        <span>Попробовать еще раз</span>
                    </Link>

                    <Link
                        href="/billing"
                        class="inline-flex items-center justify-center gap-2 w-full bg-slate-200 hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-900 dark:text-white font-semibold py-3 px-8 rounded-lg transition-all duration-300"
                    >
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L9 4.414V16a1 1 0 102 0V4.414l6.293 6.293a1 1 0 001.414-1.414l-7-7z" />
                        </svg>
                        <span>Вернуться в личный кабинет</span>
                    </Link>
                </div>
            </div>

            <div :class="['mt-8 text-center transition-all duration-700 delay-500', isVisible ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0']">
                <p class="text-sm text-slate-600 dark:text-slate-400">
                    Если проблема повторяется,
                    <a href="mailto:support@example.com" class="text-red-600 dark:text-red-400 hover:underline font-semibold">
                        свяжитесь с поддержкой
                    </a>
                </p>
            </div>
        </div>
    </div>
</template>
