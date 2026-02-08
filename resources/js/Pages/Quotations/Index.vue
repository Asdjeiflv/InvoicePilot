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

interface Quotation {
    id: number;
    quotation_no: string;
    client: Client;
    issue_date: string;
    expiry_date: string;
    total: number;
    status: string;
}

interface Props {
    quotations: {
        data: Quotation[];
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
        status?: string;
        client_id?: number;
        per_page?: number;
    };
}

const props = defineProps<Props>();

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const perPage = ref(props.filters.per_page || 15);

const statusOptions = [
    { value: '', label: 'すべて' },
    { value: 'draft', label: '下書き' },
    { value: 'sent', label: '送付済み' },
    { value: 'approved', label: '承認済み' },
    { value: 'rejected', label: '却下' },
    { value: 'expired', label: '期限切れ' },
];

const statusBadgeClass = (quotationStatus: string) => {
    const classes: Record<string, string> = {
        draft: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        sent: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        approved: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        rejected: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
        expired: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
    };
    return classes[quotationStatus] || 'bg-gray-100 text-gray-800';
};

const statusLabel = (quotationStatus: string) => {
    const labels: Record<string, string> = {
        draft: '下書き',
        sent: '送付済み',
        approved: '承認済み',
        rejected: '却下',
        expired: '期限切れ',
    };
    return labels[quotationStatus] || quotationStatus;
};

const searchQuotations = debounce(() => {
    router.get(
        route('quotations.index'),
        {
            search: search.value,
            status: status.value,
            per_page: perPage.value,
        },
        {
            preserveState: true,
            replace: true,
        }
    );
}, 300);

watch([search, status, perPage], () => {
    searchQuotations();
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
    <Head title="見積一覧" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    見積一覧
                </h2>
                <Link :href="route('quotations.create')">
                    <PrimaryButton>新規見積作成</PrimaryButton>
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
                                    placeholder="見積番号、取引先名で検索..."
                                    class="mt-1 w-full"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    ステータス
                                </label>
                                <select
                                    v-model="status"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                >
                                    <option
                                        v-for="option in statusOptions"
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
                                        見積番号
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                        取引先
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                        発行日
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                        有効期限
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                        金額
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                        ステータス
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                        操作
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                <tr
                                    v-for="quotation in quotations.data"
                                    :key="quotation.id"
                                    class="hover:bg-gray-50 dark:hover:bg-gray-700"
                                >
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <Link
                                            :href="route('quotations.show', quotation.id)"
                                            class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400"
                                        >
                                            {{ quotation.quotation_no }}
                                        </Link>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                        {{ quotation.client.company_name }}
                                        <span class="text-gray-500">({{ quotation.client.code }})</span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                        {{ formatDate(quotation.issue_date) }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                        {{ formatDate(quotation.expiry_date) }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ formatCurrency(quotation.total) }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span
                                            :class="statusBadgeClass(quotation.status)"
                                            class="inline-flex rounded-full px-2 text-xs font-semibold leading-5"
                                        >
                                            {{ statusLabel(quotation.status) }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                        <Link
                                            :href="route('quotations.show', quotation.id)"
                                            class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400"
                                        >
                                            詳細
                                        </Link>
                                        <Link
                                            v-if="['draft', 'sent'].includes(quotation.status)"
                                            :href="route('quotations.edit', quotation.id)"
                                            class="ml-4 text-gray-600 hover:text-gray-900 dark:text-gray-400"
                                        >
                                            編集
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="quotations.data.length === 0">
                                    <td colspan="7" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                        見積がありません
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="quotations.last_page > 1"
                        class="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-800 sm:px-6"
                    >
                        <div class="flex flex-1 justify-between sm:hidden">
                            <Link
                                v-if="quotations.links[0].url"
                                :href="quotations.links[0].url"
                                class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                前へ
                            </Link>
                            <Link
                                v-if="quotations.links[quotations.links.length - 1].url"
                                :href="quotations.links[quotations.links.length - 1].url"
                                class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                次へ
                            </Link>
                        </div>
                        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                    全
                                    <span class="font-medium">{{ quotations.total }}</span>
                                    件中
                                    <span class="font-medium">
                                        {{ (quotations.current_page - 1) * quotations.per_page + 1 }}
                                    </span>
                                    -
                                    <span class="font-medium">
                                        {{
                                            Math.min(
                                                quotations.current_page * quotations.per_page,
                                                quotations.total
                                            )
                                        }}
                                    </span>
                                    件を表示
                                </p>
                            </div>
                            <div>
                                <nav class="inline-flex -space-x-px rounded-md shadow-sm">
                                    <Link
                                        v-for="(link, index) in quotations.links"
                                        :key="index"
                                        :href="link.url || '#'"
                                        :class="[
                                            'relative inline-flex items-center px-4 py-2 text-sm font-medium',
                                            link.active
                                                ? 'z-10 bg-indigo-600 text-white'
                                                : 'bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300',
                                            index === 0 ? 'rounded-l-md' : '',
                                            index === quotations.links.length - 1 ? 'rounded-r-md' : '',
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
