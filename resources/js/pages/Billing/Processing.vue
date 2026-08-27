<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { onMounted, onUnmounted, ref } from 'vue';
import { route } from 'ziggy-js';

interface Props {
    orderId?: string;
}

const props = defineProps<Props>();

const checkInterval = ref<ReturnType<typeof setInterval> | null>(null);
const isChecking = ref(true);
const errorCount = ref(0);
const maxErrorRetries = 30; // 60 секунд с интервалом 2 секунды

const checkPaymentStatus = async (orderId: string) => {
    try {
        const response = await axios.get(`/orders/${orderId}/status`);
        const { status, error } = response.data;

        if (status === 'succeeded') {
            clearInterval(checkInterval.value!);
            isChecking.value = false;
            router.visit('/billing/success/' + encodeURIComponent(orderId));
        } else if (status === 'canceled') {
            clearInterval(checkInterval.value!);
            isChecking.value = false;
            router.visit(
                '/billing/failed?error=' +
                    encodeURIComponent(error || 'Неизвестная ошибка'),
            );
        } else if (errorCount.value >= maxErrorRetries && status === 'pending') {
            clearInterval(checkInterval.value!);
            router.visit(route('billing.index'));
        }

        // Сброс счетчика ошибок при успешном запросе
        // errorCount.value = 0;
        errorCount.value++;
    } catch (error) {

        // Если слишком много ошибок, редирект на страницу ошибки
        if (errorCount.value >= maxErrorRetries) {
            clearInterval(checkInterval.value!);
            isChecking.value = false;
            router.visit(
                '/billing/failed?error=' +
                    encodeURIComponent('Время ожидания истекло'),
            );
        }
    }
};

onMounted(() => {
    // Начальная проверка сразу при монтировании
    const orderId = props.orderId;

    if (orderId) {
        checkPaymentStatus(orderId);

        // Устанавливаем интервал для регулярной проверки каждые 2 секунды
        checkInterval.value = setInterval(() => {
            checkPaymentStatus(orderId);
        }, 2000);
    }
});

onUnmounted(() => {
    if (checkInterval.value) {
        clearInterval(checkInterval.value);
    }
});
</script>

<template>
    <Head title="Обработка платежа" />

    <div
        class="flex min-h-screen items-center justify-center bg-gradient-to-br from-slate-50 to-slate-100 px-4 dark:from-slate-950 dark:to-slate-900"
    >
        <div class="text-center">
            <!-- Animated Spinner -->
            <div class="mb-8 flex justify-center">
                <div
                    :class="[
                        'relative h-20 w-20 rounded-full border-4 border-slate-200 dark:border-slate-700',
                        isChecking &&
                            'animate-spin border-t-blue-600 border-r-blue-600',
                    ]"
                >
                    <!-- Inner animated ring -->
                    <div
                        v-if="isChecking"
                        class="absolute inset-0 animate-pulse rounded-full border-4 border-transparent border-b-blue-400"
                    ></div>
                </div>
            </div>

            <!-- Main Text -->
            <h1
                class="mb-4 text-3xl font-bold text-slate-900 sm:text-4xl dark:text-white"
            >
                Проверяем статус платежа...
            </h1>

            <!-- Subtitle -->
            <p class="mb-8 text-lg text-slate-600 dark:text-slate-400">
                Пожалуйста, не закрывайте вкладку
            </p>

            <!-- Status Info -->
            <div
                class="mx-auto max-w-sm rounded-xl bg-white p-6 shadow-lg dark:bg-slate-800"
            >
                <div
                    class="flex items-center justify-center gap-3 text-slate-600 dark:text-slate-400"
                >
                    <svg
                        class="h-5 w-5 animate-spin"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        ></circle>
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        ></path>
                    </svg>
                    <span class="text-sm font-medium">
                        Попытка {{ errorCount + 1 }} из {{ maxErrorRetries }}
                    </span>
                </div>
            </div>

            <!-- Help Text -->
            <p class="mt-8 text-sm text-slate-500 dark:text-slate-500">
                Если страница не обновится в течение минуты,
                <a
                    href="#"
                    class="text-blue-600 hover:underline dark:text-blue-400"
                >
                    свяжитесь с поддержкой
                </a>
            </p>
        </div>
    </div>
</template>
