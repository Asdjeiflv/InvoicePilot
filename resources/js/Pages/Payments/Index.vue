<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { debounce } from 'lodash';

interface Client {
    id: number;
    code: string;
    company_name: string;
}

interface Invoice {
    id: number;
    invoice_no: string;
    client_id: number;
    client: Client;
}

interface Payment {
    id: number;
    invoice: Invoice;
    payment_date: string;
    amount: number;
    method: string;
    reference_no: string | null;
}

interface Props {
    payments: {
        data: Payment[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
    };
    filters: {
        search?: string;
        method?: string;
        invoice_id?: number;
        per_page?: number;
    };
}

const props = defineProps<Props>();

const search = ref(props.filters.search || '');
const method = ref(props.filters.method || '');
const perPage = ref(props.filters.per_page || 15);

const methodOptions = [
    { value: '', label: 'すべて' },
    { value: 'bank_transfer', label: '銀行振込' },
    { value: 'cash', label: '現金' },
    { value: 'credit_card', label: 'クレジットカード' },
    { value: 'check', label: '小切手' },
    { value: 'other', label: 'その他' },
];

const methodLabel = (paymentMethod: string) => {
    const labels: Record<string, string> = {
        bank_transfer: '銀行振込',
        cash: '現金',
        credit_card: 'クレジットカード',
        check: '小切手',
        other: 'その他',
    };
    return labels[paymentMethod] || paymentMethod;
};

const searchPayments = debounce(() => {
    router.get(
        route('payments.index'),
        {
            search: search.value,
            method: method.value,
            per_page: perPage.value,
        },
        {
            preserveState: true,
            replace: true,
        }
    );
}, 300);

watch([search, method, perPage], () => {
    searchPayments();
});

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('ja-JP', {
        style: 'currency',
        currency: 'JPY',
    }).format(amount);
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('ja-JP');
};
</script>

<template>
    <Head title="入金一覧" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    入金一覧
                </h2>
                <Link :href="route('payments.create')">
                    <PrimaryButton>新規入金記録</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Filters -->
                <div class="mb-6 overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    検索
                                </label>
                                <TextInput
                                    v-model="search"
                                    type="text"
                                    placeholder="請求書番号、参照番号で検索..."
                                    class="mt-1 w-full"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    支払方法
                                </label>
                                <select
                                    v-model="method"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                >
                                    <option
                                        v-for="option in methodOptions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    表示件数
                                </label>
                                <select
                                    v-model="perPage"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                >
                                    <option :value="15">15件</option>
                                    <option :value="30">30件</option>
                                    <option :value="50">50件</option>
                                    <option :value="100">100件</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                        入金日
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                        請求書
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                        取引先
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                        入金額
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                        支払方法
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                        参照番号
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                        操作
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                <tr
                                    v-for="payment in payments.data"
                                    :key="payment.id"
                                    class="hover:bg-gray-50 dark:hover:bg-gray-700"
                                >
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                        {{ formatDate(payment.payment_date) }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <Link
                                            :href="route('invoices.show', payment.invoice.id)"
                                            class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400"
                                        >
                                            {{ payment.invoice.invoice_no }}
                                        </Link>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                        {{ payment.invoice.client.company_name }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ formatCurrency(payment.amount) }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                        {{ methodLabel(payment.method) }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                        {{ payment.reference_no || '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                        <Link
                                            :href="route('payments.show', payment.id)"
                                            class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400"
                                        >
                                            詳細
                                        </Link>
                                        <Link
                                            :href="route('payments.edit', payment.id)"
                                            class="ml-4 text-gray-600 hover:text-gray-900 dark:text-gray-400"
                                        >
                                            編集
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="payments.data.length === 0">
                                    <td colspan="7" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                        入金記録がありません
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="payments.last_page > 1"
                        class="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-800 sm:px-6"
                    >
                        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                    全
                                    <span class="font-medium">{{ payments.total }}</span>
                                    件中
                                    <span class="font-medium">
                                        {{ (payments.current_page - 1) * payments.per_page + 1 }}
                                    </span>
                                    -
                                    <span class="font-medium">
                                        {{
                                            Math.min(
                                                payments.current_page * payments.per_page,
                                                payments.total
                                            )
                                        }}
                                    </span>
                                    件を表示
                                </p>
                            </div>
                            <div>
                                <nav class="inline-flex -space-x-px rounded-md shadow-sm">
                                    <Link
                                        v-for="(link, index) in payments.links"
                                        :key="index"
                                        :href="link.url || '#'"
                                        :class="[
                                            'relative inline-flex items-center px-4 py-2 text-sm font-medium',
                                            link.active
                                                ? 'z-10 bg-indigo-600 text-white'
                                                : 'bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300',
                                            index === 0 ? 'rounded-l-md' : '',
                                            index === payments.links.length - 1 ? 'rounded-r-md' : '',
                                            !link.url ? 'cursor-not-allowed opacity-50' : '',
                                        ]"
                                        v-html="link.label"
                                    />
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
