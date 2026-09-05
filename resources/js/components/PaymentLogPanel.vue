<script setup lang="ts">
import { computed } from 'vue';
import type { Order } from '@/models/Order';
import type { Transaction } from '@/models/Transaction';

type LogType = 'info' | 'success' | 'error';

interface LogEntry {
    id: string;
    timestamp: string;
    sortTime: number;
    message: string;
    type: LogType;
}

const props = defineProps<{
    orders: Order[];
}>();

const formatTime = (value: string | null, fallback: string): string => {
    if (!value) {
        return fallback;
    }

    const date = new Date(value);
    return Number.isNaN(date.getTime())
        ? fallback
        : date.toLocaleTimeString('ru-RU', {
              hour: '2-digit',
              minute: '2-digit',
              second: '2-digit',
          });
};

const transactionLogs = (order: Order, transaction: Transaction): LogEntry[] => {
    const timestamp = formatTime(transaction.created_at, formatTime(order.created_at, '--:--:--'));
    const sortTime = new Date(transaction.created_at ?? order.created_at).getTime();
    const logs: LogEntry[] = [
        {
            id: `${transaction.id}:created`,
            timestamp,
            sortTime,
            message: `Инициализация транзакции по Заказу #${transaction.order_id}. Сгенерирован внутренний ULID: ${transaction.id}`,
            type: 'info',
        },
    ];

    if (transaction.gateway_payment_id) {
        logs.push({
            id: `${transaction.id}:gateway`,
            timestamp,
            sortTime,
            message: `Запрос отправлен в API ЮKassa. Получен внешний Gateway ID: ${transaction.gateway_payment_id}. Установлен Идемпотентный ключ.`,
            type: 'info',
        });
    }

    if (transaction.status === 'succeeded') {
        logs.push({
            id: `${transaction.id}:succeeded`,
            timestamp,
            sortTime,
            message:
                'АСИНХРОННЫЙ ВЕБХУК: Получено событие payment.succeeded. Очередь [ProcessYookassaWebhookJob] запущена. Суммы верифицированы. Заказ переведен в статус COMPLETED.',
            type: 'success',
        });
    }

    if (transaction.status === 'canceled') {
        logs.push({
            id: `${transaction.id}:canceled`,
            timestamp,
            sortTime,
            message: `СБОЙ ТРАНЗАКЦИИ: Получено событие payment.canceled. Запись в JSON 'cancellation_details' зафиксирована.${transaction.error_message ? ` ${transaction.error_message}` : ''}`,
            type: 'error',
        });
    }

    return logs;
};

const logs = computed<LogEntry[]>(() =>
    props.orders
        .flatMap((order) =>
            order.transactions.flatMap((transaction) => transactionLogs(order, transaction)),
        )
        .sort((first, second) => second.sortTime - first.sortTime),
);

const logColor = (type: LogType): string => {
    if (type === 'success') {
        return 'text-emerald-400';
    }

    if (type === 'error') {
        return 'text-rose-400';
    }

    return 'text-sky-400';
};

const logIcon = (type: LogType): string => {
    if (type === 'success') {
        return '✓';
    }

    if (type === 'error') {
        return '✗';
    }

    return '→';
};

const messageParts = (message: string): string[] =>
    message.split(/(COMPLETED|succeeded|ULID|Gateway ID|payment\.canceled)/g);

const isKeyword = (part: string): boolean =>
    /^(COMPLETED|succeeded|ULID|Gateway ID|payment\.canceled)$/.test(part);
</script>

<template>
    <section
        class="overflow-hidden rounded-lg border border-gray-800 bg-black font-mono text-xs text-emerald-400 shadow-2xl"
        aria-label="Логи платежных операций"
    >
        <header class="flex items-center justify-between border-b border-gray-800 bg-gray-950 px-4 py-3">
            <div class="flex items-center gap-2">
                <span class="h-2 w-2 animate-pulse rounded-full bg-emerald-400" />
                <span class="font-semibold tracking-wider">LIVE PAYMENT STREAM</span>
            </div>
            <span class="text-gray-500">{{ logs.length }} events</span>
        </header>

        <div class="h-[500px] overflow-y-auto p-4">
            <div v-if="logs.length" class="space-y-2">
                <div
                    v-for="log in logs"
                    :key="log.id"
                    class="flex items-start gap-2 leading-relaxed text-gray-400"
                >
                    <span class="shrink-0 text-gray-600">[{{ log.timestamp }}]</span>
                    <span :class="['shrink-0', logColor(log.type)]">{{ logIcon(log.type) }}</span>
                    <span>
                        <template v-for="(part, index) in messageParts(log.message)" :key="`${log.id}:${index}`">
                            <strong v-if="isKeyword(part)" :class="logColor(log.type)">{{ part }}</strong>
                            <template v-else>{{ part }}</template>
                        </template>
                    </span>
                </div>
            </div>
            <p v-else class="py-20 text-center text-gray-600">Нет событий для отображения</p>
        </div>
    </section>
</template>
