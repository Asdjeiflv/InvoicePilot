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

interface InvoiceItem {
    id: number;
    description: string;
    quantity: number;
    unit_price: number;
    tax_rate: number;
    line_total: number;
}

interface Payment {
    id: number;
    payment_date: string;
    amount: number;
    method: string;
}

interface Quotation {
    id: number;
    quotation_no: string;
}

interface Invoice {
    id: number;
    invoice_no: string;
    client: Client;
    issue_date: string;
    due_date: string;
    subtotal: number;
    tax_total: number;
    total: number;
    paid_amount: number;
    balance_due: number;
    status: string;
    notes: string | null;
    items: InvoiceItem[];
    payments: Payment[];
    quotation: Quotation | null;
}

interface Props {
    invoice: Invoice;
}

const props = defineProps<Props>();

const statusBadgeClass = (status: string) => {
    const classes: Record<string, string> = {
        draft: 'bg-gray-100 text-gray-800',
        issued: 'bg-blue-100 text-blue-800',
        partial_paid: 'bg-yellow-100 text-yellow-800',
        paid: 'bg-green-100 text-green-800',
        overdue: 'bg-red-100 text-red-800',
        canceled: 'bg-gray-100 text-gray-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const statusLabel = (status: string) => {
    const labels: Record<string, string> = {
        draft: '下書き',
        issued: '発行済み',
        partial_paid: '一部入金',
        paid: '支払済み',
        overdue: '期限切れ',
        canceled: 'キャンセル',
    };
    return labels[status] || status;
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('ja-JP', {
        style: 'currency',
        currency: 'JPY',
        maximumFractionDigits: 0,
        minimumFractionDigits: 0,
    }).format(amount);
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('ja-JP');
};

const issueInvoice = () => {
    if (confirm('この請求書を発行しますか？')) {
        router.post(route('invoices.issue', props.invoice.id));
    }
};

const cancelInvoice = () => {
    if (confirm('この請求書をキャンセルしますか？')) {
        router.post(route('invoices.cancel', props.invoice.id));
    }
};

const deleteInvoice = () => {
    if (confirm('この請求書を削除してもよろしいですか？')) {
        router.delete(route('invoices.destroy', props.invoice.id));
    }
};

const recordPayment = () => {
    router.get(route('payments.create', { invoice_id: props.invoice.id }));
};
</script>

<template>
    <Head :title="`請求書詳細 - ${invoice.invoice_no}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    請求書詳細
                </h2>
                <Link :href="route('invoices.index')">
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
                                    {{ invoice.invoice_no }}
                                </h3>
                                <span
                                    :class="statusBadgeClass(invoice.status)"
                                    class="mt-2 inline-flex rounded-full px-3 py-1 text-sm font-semibold"
                                >
                                    {{ statusLabel(invoice.status) }}
                                </span>
                            </div>
                            <div class="flex gap-2">
                                <PrimaryButton
                                    v-if="invoice.status === 'draft'"
                                    @click="issueInvoice"
                                >
                                    発行
                                </PrimaryButton>
                                <Link
                                    v-if="['draft', 'issued'].includes(invoice.status)"
                                    :href="route('invoices.edit', invoice.id)"
                                >
                                    <SecondaryButton>編集</SecondaryButton>
                                </Link>
                                <PrimaryButton
                                    v-if="['issued', 'partial_paid', 'overdue'].includes(invoice.status)"
                                    @click="recordPayment"
                                >
                                    入金記録
                                </PrimaryButton>
                                <SecondaryButton
                                    v-if="['draft', 'issued'].includes(invoice.status)"
                                    @click="cancelInvoice"
                                >
                                    キャンセル
                                </SecondaryButton>
                                <DangerButton
                                    v-if="invoice.payments.length === 0"
                                    @click="deleteInvoice"
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
                                            {{ invoice.client.company_name }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-gray-500 dark:text-gray-400">取引先コード</dt>
                                        <dd class="text-gray-900 dark:text-gray-100">
                                            {{ invoice.client.code }}
                                        </dd>
                                    </div>
                                    <div v-if="invoice.client.contact_name">
                                        <dt class="text-gray-500 dark:text-gray-400">担当者</dt>
                                        <dd class="text-gray-900 dark:text-gray-100">
                                            {{ invoice.client.contact_name }}
                                        </dd>
                                    </div>
                                </dl>
                            </div>

                            <div>
                                <h4 class="mb-3 font-semibold text-gray-700 dark:text-gray-300">
                                    請求書情報
                                </h4>
                                <dl class="space-y-2 text-sm">
                                    <div>
                                        <dt class="text-gray-500 dark:text-gray-400">発行日</dt>
                                        <dd class="text-gray-900 dark:text-gray-100">
                                            {{ formatDate(invoice.issue_date) }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-gray-500 dark:text-gray-400">支払期限</dt>
                                        <dd class="text-gray-900 dark:text-gray-100">
                                            {{ formatDate(invoice.due_date) }}
                                        </dd>
                                    </div>
                                    <div v-if="invoice.quotation">
                                        <dt class="text-gray-500 dark:text-gray-400">見積書</dt>
                                        <dd>
                                            <Link
                                                :href="route('quotations.show', invoice.quotation.id)"
                                                class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400"
                                            >
                                                {{ invoice.quotation.quotation_no }}
                                            </Link>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-gray-500 dark:text-gray-400">入金済額</dt>
                                        <dd class="text-gray-900 dark:text-gray-100">
                                            {{ formatCurrency(invoice.paid_amount) }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-gray-500 dark:text-gray-400">残高</dt>
                                        <dd class="text-gray-900 dark:text-gray-100">
                                            {{ formatCurrency(invoice.balance_due) }}
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
                                    <tr v-for="item in invoice.items" :key="item.id">
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
                                            {{ formatCurrency(invoice.subtotal) }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" class="px-6 py-3 text-right text-sm font-medium text-gray-700 dark:text-gray-300">
                                            消費税
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-3 text-right text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ formatCurrency(invoice.tax_total) }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" class="px-6 py-3 text-right text-base font-bold text-gray-900 dark:text-gray-100">
                                            合計
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-3 text-right text-base font-bold text-indigo-600 dark:text-indigo-400">
                                            {{ formatCurrency(invoice.total) }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div
                    v-if="invoice.notes"
                    class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800"
                >
                    <div class="p-6">
                        <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">
                            備考
                        </h3>
                        <p class="whitespace-pre-wrap text-sm text-gray-700 dark:text-gray-300">
                            {{ invoice.notes }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
