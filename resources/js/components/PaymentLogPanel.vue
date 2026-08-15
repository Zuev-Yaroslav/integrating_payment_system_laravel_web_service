<script setup lang="ts">
import { onMounted, ref } from 'vue';

interface LogEntry {
    timestamp: string;
    message: string;
    type: 'info' | 'success' | 'warning' | 'error';
}

const isExpanded = ref(false);
const logs = ref<LogEntry[]>([]);

// Пример логов для демонстрации
const sampleLogs: LogEntry[] = [
    {
        timestamp: '14:32:01',
        message: 'Инициализирован заказ ULID: 01HZN4M5Z6K8P2Q3R4S5T6U7V Сумма: 1000.00 RUB',
        type: 'info',
    },
    {
        timestamp: '14:32:05',
        message: 'Пользователь перенаправлен на шлюз ЮKassa (Idempotence-Key отправлен)',
        type: 'info',
    },
    {
        timestamp: '14:32:20',
        message: 'Получен Webhook от ЮKassa: payment.succeeded. Подпись валидна.',
        type: 'success',
    },
    {
        timestamp: '14:32:20',
        message: 'Заказ 01HZN4M5Z6K8P2Q3R4S5T6U7V переведен в статус COMPLETED. Доступ предоставлен.',
        type: 'success',
    },
];

onMounted(() => {
    // В реальном приложении логи будут приходить через WebSocket или Long Polling
    logs.value = sampleLogs;
});

const toggleExpanded = () => {
    isExpanded.value = !isExpanded.value;
};

const getLogColor = (type: LogEntry['type']) => {
    switch (type) {
        case 'success':
            return 'text-green-400';
        case 'error':
            return 'text-red-400';
        case 'warning':
            return 'text-yellow-400';
        case 'info':
        default:
            return 'text-blue-400';
    }
};

const getLogIcon = (type: LogEntry['type']) => {
    switch (type) {
        case 'success':
            return '✓';
        case 'error':
            return '✗';
        case 'warning':
            return '⚠';
        case 'info':
        default:
            return '→';
    }
};
</script>

<template>
    <div class="fixed bottom-4 right-4 z-40 max-w-sm">
        <!-- Toggle Button -->
        <button
            @click="toggleExpanded"
            class="flex items-center justify-center gap-2 bg-gray-900 hover:bg-gray-800 text-green-400 font-mono text-sm px-4 py-2 rounded-t-lg rounded-b-lg border border-gray-700 hover:border-gray-600 transition-all duration-300 w-full mb-1 shadow-lg"
            :class="isExpanded && 'rounded-b-none'"
        >
            <svg v-if="!isExpanded" class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
            <svg v-else class="w-4 h-4 transform rotate-180" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
            <span class="text-xs font-semibold">Payment Logger</span>
            <span class="text-xs text-gray-500 ml-auto">{{ logs.length }} {{ logs.length === 1 ? 'event' : 'events' }}</span>
        </button>

        <!-- Logs Panel -->
        <transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-2"
        >
            <div
                v-show="isExpanded"
                class="bg-gray-900 border border-gray-700 rounded-b-lg shadow-2xl overflow-hidden"
            >
                <!-- Logs Container -->
                <div class="max-h-64 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-700 scrollbar-track-gray-900">
                    <div class="font-mono text-xs p-4 space-y-1">
                        <div v-for="(log, index) in logs" :key="index" class="flex gap-2 text-gray-400 hover:text-gray-300 transition-colors">
                            <span class="text-gray-600 flex-shrink-0 w-8">[{{ log.timestamp }}]</span>
                            <span :class="['flex-shrink-0 w-2', getLogColor(log.type)]">{{ getLogIcon(log.type) }}</span>
                            <span class="text-gray-300 flex-1 break-words">{{ log.message }}</span>
                        </div>

                        <div v-if="logs.length === 0" class="text-gray-600 py-8 text-center">
                            Нет событий для отображения
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="border-t border-gray-700 bg-gray-950 px-4 py-2 text-xs text-gray-500 flex items-center justify-between">
                    <span class="font-semibold">Live Payment Events</span>
                    <div class="flex gap-2">
                        <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                        <span>Connected</span>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>

<style scoped>
/* Custom scrollbar styles */
::-webkit-scrollbar {
    width: 6px;
}

::-webkit-scrollbar-track {
    background: #111827;
}

::-webkit-scrollbar-thumb {
    background: #374151;
    border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
    background: #4b5563;
}
</style>
