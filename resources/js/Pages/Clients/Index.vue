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
    contact_name: string | null;
    email: string | null;
    phone: string | null;
    payment_terms_days: number;
}

interface Props {
    clients: {
        data: Client[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    filters: { search?: string };
}

const props = defineProps<Props>();
const search = ref(props.filters.search || '');

const searchClients = debounce(() => {
    router.get(route('clients.index'), { search: search.value }, { preserveState: true, replace: true });
}, 300);

watch(search, () => searchClients());
</script>

<template>
    <Head title="取引先一覧" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">取引先一覧</h2>
                <Link :href="route('clients.create')"><PrimaryButton>新規取引先登録</PrimaryButton></Link>
            </div>
        </template>
        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="mb-6 overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6">
                        <TextInput v-model="search" type="text" placeholder="取引先コード、会社名で検索..." class="w-full" />
                    </div>
                </div>
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">コード</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">会社名</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">担当者</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">連絡先</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">操作</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                <tr v-for="client in clients.data" :key="client.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="whitespace-nowrap px-6 py-4"><Link :href="route('clients.show', client.id)" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400">{{ client.code }}</Link></td>
                                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{{ client.company_name }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ client.contact_name || '-' }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ client.email || client.phone || '-' }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                        <Link :href="route('clients.show', client.id)" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400">詳細</Link>
                                        <Link :href="route('clients.edit', client.id)" class="ml-4 text-gray-600 hover:text-gray-900 dark:text-gray-400">編集</Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
