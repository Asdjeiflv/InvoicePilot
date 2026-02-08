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
    payment_terms_days: number;
    closing_day: number | null;
    notes: string | null;
}

interface Props {
    client: Client;
}

const props = defineProps<Props>();

const deleteClient = () => {
    if (confirm('この取引先を削除してもよろしいですか？')) {
        router.delete(route('clients.destroy', props.client.id));
    }
};
</script>

<template>
    <Head :title="`取引先詳細 - ${client.company_name}`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">取引先詳細</h2>
                <div class="flex gap-2">
                    <Link :href="route('clients.index')"><SecondaryButton>一覧に戻る</SecondaryButton></Link>
                    <Link :href="route('clients.edit', client.id)"><PrimaryButton>編集</PrimaryButton></Link>
                    <DangerButton @click="deleteClient">削除</DangerButton>
                </div>
            </div>
        </template>
        <div class="py-12">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6">
                        <h3 class="mb-6 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ client.company_name }}</h3>
                        <dl class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div><dt class="text-sm font-medium text-gray-500 dark:text-gray-400">取引先コード</dt><dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ client.code }}</dd></div>
                            <div v-if="client.contact_name"><dt class="text-sm font-medium text-gray-500 dark:text-gray-400">担当者名</dt><dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ client.contact_name }}</dd></div>
                            <div v-if="client.email"><dt class="text-sm font-medium text-gray-500 dark:text-gray-400">メールアドレス</dt><dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ client.email }}</dd></div>
                            <div v-if="client.phone"><dt class="text-sm font-medium text-gray-500 dark:text-gray-400">電話番号</dt><dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ client.phone }}</dd></div>
                            <div><dt class="text-sm font-medium text-gray-500 dark:text-gray-400">支払条件</dt><dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ client.payment_terms_days }}日</dd></div>
                            <div v-if="client.closing_day"><dt class="text-sm font-medium text-gray-500 dark:text-gray-400">締日</dt><dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">毎月{{ client.closing_day }}日</dd></div>
                        </dl>
                        <div v-if="client.address" class="mt-6"><dt class="text-sm font-medium text-gray-500 dark:text-gray-400">住所</dt><dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ client.address }}</dd></div>
                        <div v-if="client.notes" class="mt-6"><dt class="text-sm font-medium text-gray-500 dark:text-gray-400">備考</dt><dd class="mt-1 whitespace-pre-wrap text-sm text-gray-900 dark:text-gray-100">{{ client.notes }}</dd></div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
