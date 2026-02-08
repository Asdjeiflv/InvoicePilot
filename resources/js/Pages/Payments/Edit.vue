<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

interface Client {
    id: number;
    code: string;
    company_name: string;
}

interface Invoice {
    id: number;
    invoice_no: string;
    client: Client;
    balance_due: number;
}

interface Payment {
    id: number;
    invoice: Invoice;
    payment_date: string;
    amount: number;
    method: string;
    reference_no: string | null;
    note: string | null;
}

interface Props {
    payment: Payment;
}

const props = defineProps<Props>();

const form = useForm({
    invoice_id: props.payment.invoice.id,
    payment_date: props.payment.payment_date,
    amount: props.payment.amount,
    method: props.payment.method,
    reference_no: props.payment.reference_no || '',
    note: props.payment.note || '',
});

const selectedInvoice = computed(() => {
    return props.payment.invoice;
});

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('ja-JP', {
        style: 'currency',
        currency: 'JPY',
        maximumFractionDigits: 0,
        minimumFractionDigits: 0,
    }).format(amount);
};

const updateAmountToBalance = () => {
    if (selectedInvoice.value) {
        form.amount = selectedInvoice.value.balance_due + payment.amount;
    }
};

const submit = () => {
    form.put(route('payments.update', props.payment.id));
};
</script>

<template>
    <Head title="入金編集" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    入金編集
                </h2>
                <Link :href="route('payments.show', payment.id)">
                    <SecondaryButton>詳細に戻る</SecondaryButton>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <form @submit.prevent="submit" class="p-6">
                        <div class="space-y-6">
                            <!-- Invoice Information (Read-only) -->
                            <div
                                v-if="selectedInvoice"
                                class="rounded-lg border border-indigo-200 bg-indigo-50 p-4 dark:border-indigo-800 dark:bg-indigo-900"
                            >
                                <h4 class="mb-2 font-semibold text-indigo-900 dark:text-indigo-100">
                                    請求書情報
                                </h4>
                                <dl class="space-y-1 text-sm">
                                    <div class="flex justify-between">
                                        <dt class="text-indigo-700 dark:text-indigo-300">取引先:</dt>
                                        <dd class="font-medium text-indigo-900 dark:text-indigo-100">
                                            {{ selectedInvoice.client.company_name }}
                                        </dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-indigo-700 dark:text-indigo-300">残高:</dt>
                                        <dd class="font-bold text-indigo-900 dark:text-indigo-100">
                                            {{ formatCurrency(selectedInvoice.balance_due) }}
                                        </dd>
                                    </div>
                                </dl>
                            </div>

                            <!-- Payment Date -->
                            <div>
                                <InputLabel for="payment_date" value="入金日 *" />
                                <TextInput
                                    id="payment_date"
                                    v-model="form.payment_date"
                                    type="date"
                                    class="mt-1 block w-full"
                                    required
                                />
                                <InputError class="mt-2" :message="form.errors.payment_date" />
                            </div>

                            <!-- Amount -->
                            <div>
                                <div class="flex items-center justify-between">
                                    <InputLabel for="amount" value="入金額 *" />
                                    <button
                                        v-if="selectedInvoice"
                                        type="button"
                                        @click="updateAmountToBalance"
                                        class="text-sm text-indigo-600 hover:text-indigo-900 dark:text-indigo-400"
                                    >
                                        残高を入力
                                    </button>
                                </div>
                                <TextInput
                                    id="amount"
                                    v-model="form.amount"
                                    type="number"
                                    step="1"
                                    min="1"
                                    class="mt-1 block w-full"
                                    required
                                />
                                <InputError class="mt-2" :message="form.errors.amount" />
                            </div>

                            <!-- Payment Method -->
                            <div>
                                <InputLabel for="method" value="支払方法 *" />
                                <select
                                    id="method"
                                    v-model="form.method"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                    required
                                >
                                    <option value="bank_transfer">銀行振込</option>
                                    <option value="cash">現金</option>
                                    <option value="credit_card">クレジットカード</option>
                                    <option value="check">小切手</option>
                                    <option value="other">その他</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.method" />
                            </div>

                            <!-- Reference Number -->
                            <div>
                                <InputLabel for="reference_no" value="参照番号" />
                                <TextInput
                                    id="reference_no"
                                    v-model="form.reference_no"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="振込番号、受領書番号など"
                                />
                                <InputError class="mt-2" :message="form.errors.reference_no" />
                            </div>

                            <!-- Note -->
                            <div>
                                <InputLabel for="note" value="備考" />
                                <textarea
                                    id="note"
                                    v-model="form.note"
                                    rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                    placeholder="特記事項があれば記入してください"
                                ></textarea>
                                <InputError class="mt-2" :message="form.errors.note" />
                            </div>
                        </div>

                        <!-- Submit -->
                        <div class="mt-8 flex items-center justify-end gap-4">
                            <Link :href="route('payments.show', payment.id)">
                                <SecondaryButton type="button">キャンセル</SecondaryButton>
                            </Link>
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                更新する
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
