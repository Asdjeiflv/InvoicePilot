<script setup lang="ts">
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
    contact_name: string | null;
    email: string | null;
    phone: string | null;
    address: string | null;
    payment_terms_days: number;
    closing_day: number | null;
    notes: string | null;
}

interface Props {
    client: Client;
}

const props = defineProps<Props>();

const form = useForm({
    code: props.client.code,
    company_name: props.client.company_name,
    contact_name: props.client.contact_name || '',
    email: props.client.email || '',
    phone: props.client.phone || '',
    address: props.client.address || '',
    payment_terms_days: props.client.payment_terms_days,
    closing_day: props.client.closing_day,
    notes: props.client.notes || '',
});

const submit = () => form.put(route('clients.update', props.client.id));
</script>

<template>
    <Head title="取引先編集" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">取引先編集 - {{ client.company_name }}</h2>
        </template>
        <div class="py-12">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <form @submit.prevent="submit" class="p-6 space-y-6">
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div><InputLabel for="code" value="取引先コード *" /><TextInput id="code" v-model="form.code" type="text" class="mt-1 block w-full" required /><InputError class="mt-2" :message="form.errors.code" /></div>
                            <div><InputLabel for="company_name" value="会社名 *" /><TextInput id="company_name" v-model="form.company_name" type="text" class="mt-1 block w-full" required /><InputError class="mt-2" :message="form.errors.company_name" /></div>
                            <div><InputLabel for="contact_name" value="担当者名" /><TextInput id="contact_name" v-model="form.contact_name" type="text" class="mt-1 block w-full" /><InputError class="mt-2" :message="form.errors.contact_name" /></div>
                            <div><InputLabel for="email" value="メールアドレス" /><TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full" /><InputError class="mt-2" :message="form.errors.email" /></div>
                            <div><InputLabel for="phone" value="電話番号" /><TextInput id="phone" v-model="form.phone" type="text" class="mt-1 block w-full" /><InputError class="mt-2" :message="form.errors.phone" /></div>
                            <div><InputLabel for="payment_terms_days" value="支払条件(日) *" /><TextInput id="payment_terms_days" v-model="form.payment_terms_days" type="number" min="0" max="365" class="mt-1 block w-full" required /><InputError class="mt-2" :message="form.errors.payment_terms_days" /></div>
                        </div>
                        <div><InputLabel for="address" value="住所" /><textarea id="address" v-model="form.address" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"></textarea><InputError class="mt-2" :message="form.errors.address" /></div>
                        <div><InputLabel for="notes" value="備考" /><textarea id="notes" v-model="form.notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"></textarea><InputError class="mt-2" :message="form.errors.notes" /></div>
                        <div class="flex items-center justify-end gap-4">
                            <Link :href="route('clients.show', client.id)"><SecondaryButton type="button">キャンセル</SecondaryButton></Link>
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">更新する</PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
