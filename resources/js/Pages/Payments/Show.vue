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
}

interface Invoice {
    id: number;
    invoice_no: string;
    client: Client;
}

interface RecordedBy {
    id: number;
    name: string;
}

interface Payment {
    id: number;
    invoice: Invoice;
    payment_date: string;
    amount: number;
    method: string;
    reference_no: string | null;
    note: string | null;
    recordedBy: RecordedBy;
    created_at: string;
}

interface Props {
    payment: Payment;
}

const props = defineProps<Props>();

const methodLabel = (method: string) => {
    const labels: Record<string, string> = {
        bank_transfer: '銀行振込',
        cash: '現金',
        credit_card: 'クレジットカード',
        check: '小切手',
        other: 'その他',
    };
    return labels[method] || method;
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

const formatDateTime = (date: string) => {
    return new Date(date).toLocaleString('ja-JP');
};

const deletePayment = () => {
    if (confirm('この入金記録を削除してもよろしいですか？')) {
        router.delete(route('payments.destroy', props.payment.id));
    }
};
</script>

<template>
    <Head title="入金詳細" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    入金詳細
                </h2>
                <div class="flex gap-2">
                    <Link :href="route('payments.index')">
                        <SecondaryButton>一覧に戻る</SecondaryButton>
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6">
                        <div class="mb-6 flex items-start justify-between">
                            <div>
                                <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                    {{ formatCurrency(payment.amount) }}
                                </h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    {{ formatDate(payment.payment_date) }} 入金
                                </p>
                            </div>
                            <div class="flex gap-2">
                                <Link :href="route('payments.edit', payment.id)">
                                    <PrimaryButton>編集</PrimaryButton>
                                </Link>
                                <DangerButton @click="deletePayment">削除</DangerButton>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <!-- Invoice Information -->
                            <div>
                                <h4 class="mb-3 font-semibold text-gray-700 dark:text-gray-300">
                                    請求書情報
                                </h4>
                                <dl class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <dt class="text-gray-500 dark:text-gray-400">請求書番号</dt>
                                        <dd>
                                            <Link
                                                :href="route('invoices.show', payment.invoice.id)"
                                                class="font-medium text-indigo-600 hover:text-indigo-900 dark:text-indigo-400"
                                            >
                                                {{ payment.invoice.invoice_no }}
                                            </Link>
                                        </dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-gray-500 dark:text-gray-400">取引先</dt>
                                        <dd class="text-gray-900 dark:text-gray-100">
                                            {{ payment.invoice.client.company_name }}
                                        </dd>
                                    </div>
                                </dl>
                            </div>

                            <hr class="border-gray-200 dark:border-gray-700" />

                            <!-- Payment Details -->
                            <div>
                                <h4 class="mb-3 font-semibold text-gray-700 dark:text-gray-300">
                                    入金詳細
                                </h4>
                                <dl class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <dt class="text-gray-500 dark:text-gray-400">入金日</dt>
                                        <dd class="text-gray-900 dark:text-gray-100">
                                            {{ formatDate(payment.payment_date) }}
                                        </dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-gray-500 dark:text-gray-400">入金額</dt>
                                        <dd class="font-bold text-gray-900 dark:text-gray-100">
                                            {{ formatCurrency(payment.amount) }}
                                        </dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-gray-500 dark:text-gray-400">支払方法</dt>
                                        <dd class="text-gray-900 dark:text-gray-100">
                                            {{ methodLabel(payment.method) }}
                                        </dd>
                                    </div>
                                    <div v-if="payment.reference_no" class="flex justify-between">
                                        <dt class="text-gray-500 dark:text-gray-400">参照番号</dt>
                                        <dd class="text-gray-900 dark:text-gray-100">
                                            {{ payment.reference_no }}
                                        </dd>
                                    </div>
                                </dl>
                            </div>

                            <!-- Notes -->
                            <div v-if="payment.note">
                                <h4 class="mb-3 font-semibold text-gray-700 dark:text-gray-300">備考</h4>
                                <p class="whitespace-pre-wrap text-sm text-gray-700 dark:text-gray-300">
                                    {{ payment.note }}
                                </p>
                            </div>

                            <hr class="border-gray-200 dark:border-gray-700" />

                            <!-- Meta Information -->
                            <div>
                                <h4 class="mb-3 font-semibold text-gray-700 dark:text-gray-300">
                                    記録情報
                                </h4>
                                <dl class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <dt class="text-gray-500 dark:text-gray-400">記録者</dt>
                                        <dd class="text-gray-900 dark:text-gray-100">
                                            {{ payment.recordedBy.name }}
                                        </dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-gray-500 dark:text-gray-400">記録日時</dt>
                                        <dd class="text-gray-900 dark:text-gray-100">
                                            {{ formatDateTime(payment.created_at) }}
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
