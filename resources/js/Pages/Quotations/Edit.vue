<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';

interface Client {
    id: number;
    code: string;
    company_name: string;
}

interface QuotationItem {
    description: string;
    quantity: number;
    unit_price: number;
    tax_rate: number;
}

interface Quotation {
    id: number;
    quotation_no: string;
    client_id: number;
    issue_date: string;
    expiry_date: string;
    items: QuotationItem[];
    notes: string | null;
}

interface Props {
    quotation: Quotation;
    clients: Client[];
}

const props = defineProps<Props>();

const form = useForm({
    client_id: props.quotation.client_id,
    issue_date: props.quotation.issue_date,
    expiry_date: props.quotation.expiry_date,
    items: props.quotation.items.map(item => ({
        description: item.description,
        quantity: item.quantity,
        unit_price: item.unit_price,
        tax_rate: item.tax_rate,
    })),
    notes: props.quotation.notes || '',
});

const addItem = () => {
    form.items.push({
        description: '',
        quantity: 1,
        unit_price: 0,
        tax_rate: 10,
    });
};

const removeItem = (index: number) => {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
    }
};

const calculateLineTotal = (item: QuotationItem) => {
    return item.quantity * item.unit_price;
};

const calculateLineTax = (item: QuotationItem) => {
    return calculateLineTotal(item) * (item.tax_rate / 100);
};

const calculateSubtotal = () => {
    return form.items.reduce((sum, item) => sum + calculateLineTotal(item), 0);
};

const calculateTaxTotal = () => {
    return form.items.reduce((sum, item) => sum + calculateLineTax(item), 0);
};

const calculateTotal = () => {
    return calculateSubtotal() + calculateTaxTotal();
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('ja-JP', {
        style: 'currency',
        currency: 'JPY',
    }).format(amount);
};

const submit = () => {
    form.put(route('quotations.update', props.quotation.id));
};
</script>

<template>
    <Head title="見積編集" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    見積編集 - {{ quotation.quotation_no }}
                </h2>
                <Link :href="route('quotations.show', quotation.id)">
                    <SecondaryButton>詳細に戻る</SecondaryButton>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <form @submit.prevent="submit" class="p-6">
                        <!-- Basic Information -->
                        <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <InputLabel for="client_id" value="取引先 *" />
                                <select
                                    id="client_id"
                                    v-model="form.client_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                    required
                                >
                                    <option :value="null">選択してください</option>
                                    <option
                                        v-for="client in clients"
                                        :key="client.id"
                                        :value="client.id"
                                    >
                                        {{ client.company_name }} ({{ client.code }})
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.client_id" />
                            </div>

                            <div>
                                <InputLabel for="issue_date" value="発行日 *" />
                                <TextInput
                                    id="issue_date"
                                    v-model="form.issue_date"
                                    type="date"
                                    class="mt-1 block w-full"
                                    required
                                />
                                <InputError class="mt-2" :message="form.errors.issue_date" />
                            </div>

                            <div>
                                <InputLabel for="expiry_date" value="有効期限 *" />
                                <TextInput
                                    id="expiry_date"
                                    v-model="form.expiry_date"
                                    type="date"
                                    class="mt-1 block w-full"
                                    required
                                />
                                <InputError class="mt-2" :message="form.errors.expiry_date" />
                            </div>
                        </div>

                        <!-- Items -->
                        <div class="mb-8">
                            <div class="mb-4 flex items-center justify-between">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    明細行
                                </h3>
                                <SecondaryButton type="button" @click="addItem">
                                    + 明細追加
                                </SecondaryButton>
                            </div>

                            <div class="space-y-4">
                                <div
                                    v-for="(item, index) in form.items"
                                    :key="index"
                                    class="rounded-lg border border-gray-200 p-4 dark:border-gray-700"
                                >
                                    <div class="mb-2 flex items-center justify-between">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            明細 {{ index + 1 }}
                                        </span>
                                        <DangerButton
                                            v-if="form.items.length > 1"
                                            type="button"
                                            @click="removeItem(index)"
                                        >
                                            削除
                                        </DangerButton>
                                    </div>

                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-12">
                                        <div class="md:col-span-6">
                                            <InputLabel :for="`description-${index}`" value="商品・サービス *" />
                                            <TextInput
                                                :id="`description-${index}`"
                                                v-model="item.description"
                                                type="text"
                                                class="mt-1 block w-full"
                                                placeholder="例: Webサイト制作"
                                                required
                                            />
                                            <InputError
                                                class="mt-2"
                                                :message="form.errors[`items.${index}.description`]"
                                            />
                                        </div>

                                        <div class="md:col-span-2">
                                            <InputLabel :for="`quantity-${index}`" value="数量 *" />
                                            <TextInput
                                                :id="`quantity-${index}`"
                                                v-model="item.quantity"
                                                type="number"
                                                step="0.01"
                                                min="0.01"
                                                class="mt-1 block w-full"
                                                required
                                            />
                                            <InputError
                                                class="mt-2"
                                                :message="form.errors[`items.${index}.quantity`]"
                                            />
                                        </div>

                                        <div class="md:col-span-2">
                                            <InputLabel :for="`unit_price-${index}`" value="単価 *" />
                                            <TextInput
                                                :id="`unit_price-${index}`"
                                                v-model="item.unit_price"
                                                type="number"
                                                step="1"
                                                min="0"
                                                class="mt-1 block w-full"
                                                required
                                            />
                                            <InputError
                                                class="mt-2"
                                                :message="form.errors[`items.${index}.unit_price`]"
                                            />
                                        </div>

                                        <div class="md:col-span-2">
                                            <InputLabel :for="`tax_rate-${index}`" value="税率(%) *" />
                                            <TextInput
                                                :id="`tax_rate-${index}`"
                                                v-model="item.tax_rate"
                                                type="number"
                                                step="0.1"
                                                min="0"
                                                max="100"
                                                class="mt-1 block w-full"
                                                required
                                            />
                                            <InputError
                                                class="mt-2"
                                                :message="form.errors[`items.${index}.tax_rate`]"
                                            />
                                        </div>
                                    </div>

                                    <div class="mt-2 text-right text-sm text-gray-600 dark:text-gray-400">
                                        小計: {{ formatCurrency(calculateLineTotal(item)) }} + 税:
                                        {{ formatCurrency(calculateLineTax(item)) }} =
                                        {{
                                            formatCurrency(calculateLineTotal(item) + calculateLineTax(item))
                                        }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Totals -->
                        <div class="mb-8 rounded-lg border border-gray-200 bg-gray-50 p-6 dark:border-gray-700 dark:bg-gray-900">
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">小計:</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">
                                        {{ formatCurrency(calculateSubtotal()) }}
                                    </span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">消費税:</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">
                                        {{ formatCurrency(calculateTaxTotal()) }}
                                    </span>
                                </div>
                                <div class="flex justify-between border-t border-gray-300 pt-2 text-lg font-bold dark:border-gray-600">
                                    <span class="text-gray-900 dark:text-gray-100">合計:</span>
                                    <span class="text-indigo-600 dark:text-indigo-400">
                                        {{ formatCurrency(calculateTotal()) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="mb-8">
                            <InputLabel for="notes" value="備考" />
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="4"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                placeholder="特記事項があれば記入してください"
                            ></textarea>
                            <InputError class="mt-2" :message="form.errors.notes" />
                        </div>

                        <!-- Submit -->
                        <div class="flex items-center justify-end gap-4">
                            <Link :href="route('quotations.show', quotation.id)">
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
