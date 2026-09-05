<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';
import PaymentLogPanel from '@/components/PaymentLogPanel.vue';
import type { Order } from '@/models/Order';

const props = defineProps<{
    orders: Order[];
}>();

const expandedOrders = ref<Set<string>>(new Set());
const retryingOrderId = ref<string | null>(null);

const statusLabel: Record<Order['status'], string> = {
    pending: 'Ожидает оплаты',
    completed: 'Завершен',
    failed: 'Ошибка',
};

const statusClasses: Record<Order['status'], string> = {
    pending: 'border-amber-500/20 bg-amber-500/10 text-amber-500',
    completed: 'border-emerald-500/20 bg-emerald-500/10 text-emerald-500',
    failed: 'border-rose-500/20 bg-rose-500/10 text-rose-500',
};

const sortedOrders = computed(() =>
    [...props.orders].sort(
        (first, second) =>
            new Date(second.created_at).getTime() - new Date(first.created_at).getTime(),
    ),
);

const shortOrderId = (id: string): string => `${id.slice(0, 8)}...`;

const formatDate = (value: string | null): string => {
    if (!value) {
        return 'Дата неизвестна';
    }

    const date = new Date(value);
    return Number.isNaN(date.getTime())
        ? 'Дата неизвестна'
        : date.toLocaleString('ru-RU', {
              dateStyle: 'medium',
              timeStyle: 'short',
          });
};

const formatAmount = (amount: number, currency: string): string =>
    new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency,
        maximumFractionDigits: 2,
    }).format(amount);

const toggleOrder = (orderId: string): void => {
    const next = new Set(expandedOrders.value);

    if (next.has(orderId)) {
        next.delete(orderId);
    } else {
        next.add(orderId);
    }

    expandedOrders.value = next;
};

const retryPayment = (orderId: string): void => {
    retryingOrderId.value = orderId;

    router.post(route('payment.retry', { orderId }), {}, {
        onFinish: () => {
            retryingOrderId.value = null;
        },
    });
};
</script>

<template>
    <Head title="История платежей" />

    <main class="min-h-screen bg-gray-900 px-4 py-8 text-white sm:px-6 lg:px-8">
        <div class="mx-auto max-w-6xl">
            <header class="mb-8">
                <p class="font-mono text-xs uppercase tracking-[0.3em] text-emerald-400">Billing / Dashboard</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight">История платежей</h1>
                <p class="mt-2 text-gray-400">Заказы, транзакции и диагностический поток событий.</p>
            </header>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                <section class="space-y-3 lg:col-span-2" aria-label="История заказов">
                    <div
                        v-for="order in sortedOrders"
                        :key="order.id"
                        class="overflow-hidden rounded-xl border border-gray-800 bg-gray-950 shadow-xl"
                    >
                        <button
                            type="button"
                            class="grid w-full grid-cols-[1fr_auto_auto] items-center gap-4 p-5 text-left transition hover:bg-gray-900"
                            :aria-expanded="expandedOrders.has(order.id)"
                            @click="toggleOrder(order.id)"
                        >
                            <div class="min-w-0">
                                <p class="truncate font-mono text-sm text-gray-200">Order #{{ shortOrderId(order.id) }}</p>
                                <p class="mt-1 text-xs text-gray-500">{{ formatDate(order.created_at) }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xl font-bold text-white">{{ formatAmount(order.amount, order.currency) }}</p>
                                <p class="mt-1 max-w-32 truncate text-xs text-gray-500">{{ order.description }}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <span :class="['rounded-full border px-2.5 py-1 text-xs font-medium', statusClasses[order.status]]">
                                    {{ statusLabel[order.status] }}
                                </span>
                                <span
                                    class="text-gray-500 transition-transform"
                                    :class="{ 'rotate-180': expandedOrders.has(order.id) }"
                                    aria-hidden="true"
                                >⌄</span>
                            </div>
                        </button>

                        <Transition
                            enter-active-class="transition duration-200 ease-out"
                            enter-from-class="opacity-0 -translate-y-2"
                            enter-to-class="opacity-100 translate-y-0"
                            leave-active-class="transition duration-150 ease-in"
                            leave-from-class="opacity-100 translate-y-0"
                            leave-to-class="opacity-0 -translate-y-2"
                        >
                            <div v-if="expandedOrders.has(order.id)" class="border-t border-gray-800 px-5 py-4">
                                <div class="mb-4 flex items-center justify-between">
                                    <h2 class="text-sm font-semibold text-gray-300">Попытки оплаты</h2>
                                    <button
                                        v-if="order.status === 'pending' || order.status === 'failed'"
                                        type="button"
                                        class="rounded-md border border-emerald-500/30 px-3 py-1.5 text-xs text-emerald-400 transition hover:bg-emerald-500/10 disabled:cursor-wait disabled:opacity-50"
                                        :disabled="retryingOrderId === order.id"
                                        @click.stop="retryPayment(order.id)"
                                    >
                                        {{ retryingOrderId === order.id ? 'Повторяем...' : 'Повторить попытку оплаты' }}
                                    </button>
                                </div>

                                <div v-if="order.transactions.length" class="divide-y divide-gray-800/80">
                                    <div
                                        v-for="transaction in order.transactions"
                                        :key="transaction.id"
                                        class="grid gap-2 py-3 text-xs text-gray-400 sm:grid-cols-[1fr_1fr_1.5fr]"
                                    >
                                        <span>{{ formatDate(transaction.created_at) }}</span>
                                        <span>Метод: {{ transaction.payment_method || 'Не указан' }}</span>
                                        <span class="break-all font-mono text-gray-500">
                                            Gateway: {{ transaction.gateway_payment_id || 'ожидает ID' }}
                                        </span>
                                        <p v-if="transaction.error_message" class="text-rose-400 sm:col-span-3">
                                            {{ transaction.error_message }}
                                        </p>
                                    </div>
                                </div>
                                <p v-else class="text-sm text-gray-500">Транзакций пока нет.</p>
                            </div>
                        </Transition>
                    </div>

                    <p v-if="!sortedOrders.length" class="rounded-xl border border-dashed border-gray-700 p-10 text-center text-gray-500">
                        История заказов пуста.
                    </p>
                </section>

                <PaymentLogPanel :orders="sortedOrders" />
            </div>
        </div>
    </main>
</template>
