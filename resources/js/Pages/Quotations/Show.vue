<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';

interface Client {
    id: number;
    code: string;
    company_name: string;
    contact_name: string | null;
    email: string | null;
    phone: string | null;
    address: string | null;
}

interface QuotationItem {
    id: number;
    description: string;
    quantity: number;
    unit_price: number;
    tax_rate: number;
    line_total: number;
}

interface Invoice {
    id: number;
    invoice_no: string;
}

interface Quotation {
    id: number;
    quotation_no: string;
    client: Client;
    issue_date: string;
    expiry_date: string;
    subtotal: number;
    tax_total: number;
    total: number;
    status: string;
    notes: string | null;
    items: QuotationItem[];
    invoices: Invoice[];
}

interface Props {
    quotation: Quotation;
}

const props = defineProps<Props>();

const statusBadgeClass = (status: string) => {
    const classes: Record<string, string> = {
        draft: 'bg-gray-100 text-gray-800',
        sent: 'bg-blue-100 text-blue-800',
        approved: 'bg-green-100 text-green-800',
        rejected: 'bg-red-100 text-red-800',
        expired: 'bg-yellow-100 text-yellow-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const statusLabel = (status: string) => {
    const labels: Record<string, string> = {
        draft: '下書き',
        sent: '送付済み',
        approved: '承認済み',
        rejected: '却下',
        expired: '期限切れ',
    };
    return labels[status] || status;
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('ja-JP', {
        style: 'currency',
        currency: 'JPY',
    }).format(amount);
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('ja-JP');
};

const approve = () => {
    if (confirm('この見積を承認しますか?')) {
        router.post(route('quotations.approve', props.quotation.id));
    }
};

const reject = () => {
    if (confirm('この見積を却下しますか?')) {
        router.post(route('quotations.reject', props.quotation.id));
    }
};

const deleteQuotation = () => {
    if (confirm('この見積を削除してもよろしいですか？')) {
        router.delete(route('quotations.destroy', props.quotation.id));
    }
};

const createInvoice = () => {
    router.get(route('invoices.create', { quotation_id: props.quotation.id }));
};
</script>

<template>
    <Head :title="`見積詳細 - ${quotation.quotation_no}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    見積詳細
                </h2>
                <Link :href="route('quotations.index')">
                    <SecondaryButton>一覧に戻る</SecondaryButton>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <!-- Header Info -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6">
                        <div class="mb-6 flex items-start justify-between">
                            <div>
                                <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                    {{ quotation.quotation_no }}
                                </h3>
                                <span
                                    :class="statusBadgeClass(quotation.status)"
                                    class="mt-2 inline-flex rounded-full px-3 py-1 text-sm font-semibold"
                                >
                                    {{ statusLabel(quotation.status) }}
                                </span>
                            </div>
                            <div class="flex gap-2">
                                <SecondaryButton
                                    v-if="quotation.status === 'draft'"
                                    @click="approve"
                                >
                                    承認
                                </SecondaryButton>
                                <SecondaryButton
                                    v-if="['draft', 'sent'].includes(quotation.status)"
                                    @click="reject"
                                >
                                    却下
                                </SecondaryButton>
                                <Link
                                    v-if="['draft', 'sent'].includes(quotation.status)"
                                    :href="route('quotations.edit', quotation.id)"
                                >
                                    <PrimaryButton>編集</PrimaryButton>
                                </Link>
                                <PrimaryButton
                                    v-if="quotation.status === 'approved' && quotation.invoices.length === 0"
                                    @click="createInvoice"
                                >
                                    請求書を作成
                                </PrimaryButton>
                                <DangerButton
                                    v-if="!(quotation.status === 'approved' && quotation.invoices.length > 0)"
                                    @click="deleteQuotation"
                                >
                                    削除
                                </DangerButton>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <h4 class="mb-3 font-semibold text-gray-700 dark:text-gray-300">
                                    取引先情報
                                </h4>
                                <dl class="space-y-2 text-sm">
                                    <div>
                                        <dt class="text-gray-500 dark:text-gray-400">会社名</dt>
                                        <dd class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ quotation.client.company_name }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-gray-500 dark:text-gray-400">取引先コード</dt>
                                        <dd class="text-gray-900 dark:text-gray-100">
                                            {{ quotation.client.code }}
                                        </dd>
                                    </div>
                                    <div v-if="quotation.client.contact_name">
                                        <dt class="text-gray-500 dark:text-gray-400">担当者</dt>
                                        <dd class="text-gray-900 dark:text-gray-100">
                                            {{ quotation.client.contact_name }}
                                        </dd>
                                    </div>
                                </dl>
                            </div>

                            <div>
                                <h4 class="mb-3 font-semibold text-gray-700 dark:text-gray-300">
                                    見積情報
                                </h4>
                                <dl class="space-y-2 text-sm">
                                    <div>
                                        <dt class="text-gray-500 dark:text-gray-400">発行日</dt>
                                        <dd class="text-gray-900 dark:text-gray-100">
                                            {{ formatDate(quotation.issue_date) }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-gray-500 dark:text-gray-400">有効期限</dt>
                                        <dd class="text-gray-900 dark:text-gray-100">
                                            {{ formatDate(quotation.expiry_date) }}
                                        </dd>
                                    </div>
                                    <div v-if="quotation.invoices.length > 0">
                                        <dt class="text-gray-500 dark:text-gray-400">請求書</dt>
                                        <dd>
                                            <Link
                                                v-for="invoice in quotation.invoices"
                                                :key="invoice.id"
                                                :href="route('invoices.show', invoice.id)"
                                                class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400"
                                            >
                                                {{ invoice.invoice_no }}
                                            </Link>
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Items -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6">
                        <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">
                            明細行
                        </h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                            商品・サービス
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                            数量
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                            単価
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                            税率
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                            小計
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                    <tr v-for="item in quotation.items" :key="item.id">
                                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                            {{ item.description }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-900 dark:text-gray-100">
                                            {{ item.quantity }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-900 dark:text-gray-100">
                                            {{ formatCurrency(item.unit_price) }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-900 dark:text-gray-100">
                                            {{ item.tax_rate }}%
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ formatCurrency(item.line_total) }}
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <td colspan="4" class="px-6 py-3 text-right text-sm font-medium text-gray-700 dark:text-gray-300">
                                            小計
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-3 text-right text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ formatCurrency(quotation.subtotal) }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" class="px-6 py-3 text-right text-sm font-medium text-gray-700 dark:text-gray-300">
                                            消費税
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-3 text-right text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ formatCurrency(quotation.tax_total) }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" class="px-6 py-3 text-right text-base font-bold text-gray-900 dark:text-gray-100">
                                            合計
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-3 text-right text-base font-bold text-indigo-600 dark:text-indigo-400">
                                            {{ formatCurrency(quotation.total) }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div
                    v-if="quotation.notes"
                    class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800"
                >
                    <div class="p-6">
                        <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">
                            備考
                        </h3>
                        <p class="whitespace-pre-wrap text-sm text-gray-700 dark:text-gray-300">
                            {{ quotation.notes }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
