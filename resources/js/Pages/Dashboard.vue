<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200"
            >
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Welcome Message -->
                <div class="mb-6 overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <h3 class="text-lg font-semibold mb-2">ようこそ、InvoicePilotへ！</h3>
                        <p class="text-gray-600 dark:text-gray-400">以下のメニューから操作を開始してください。</p>
                    </div>
                </div>

                <!-- Quick Access Cards -->
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <!-- Clients Card -->
                    <Link
                        :href="route('clients.index')"
                        class="group block overflow-hidden rounded-lg bg-white p-6 shadow-sm transition hover:shadow-lg dark:bg-gray-800"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    取引先
                                </h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    取引先の管理
                                </p>
                            </div>
                            <div class="rounded-full bg-blue-100 p-3 dark:bg-blue-900">
                                <svg class="h-6 w-6 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="mt-4">
                            <span class="text-sm font-medium text-blue-600 group-hover:underline dark:text-blue-400">
                                管理画面へ →
                            </span>
                        </div>
                    </Link>

                    <!-- Quotations Card -->
                    <Link
                        :href="route('quotations.index')"
                        class="group block overflow-hidden rounded-lg bg-white p-6 shadow-sm transition hover:shadow-lg dark:bg-gray-800"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    見積
                                </h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    見積書の作成・管理
                                </p>
                            </div>
                            <div class="rounded-full bg-green-100 p-3 dark:bg-green-900">
                                <svg class="h-6 w-6 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                        </div>
                        <div class="mt-4">
                            <span class="text-sm font-medium text-green-600 group-hover:underline dark:text-green-400">
                                管理画面へ →
                            </span>
                        </div>
                    </Link>

                    <!-- Invoices Card -->
                    <Link
                        :href="route('invoices.index')"
                        class="group block overflow-hidden rounded-lg bg-white p-6 shadow-sm transition hover:shadow-lg dark:bg-gray-800"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    請求書
                                </h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    請求書の発行・管理
                                </p>
                            </div>
                            <div class="rounded-full bg-purple-100 p-3 dark:bg-purple-900">
                                <svg class="h-6 w-6 text-purple-600 dark:text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
                                </svg>
                            </div>
                        </div>
                        <div class="mt-4">
                            <span class="text-sm font-medium text-purple-600 group-hover:underline dark:text-purple-400">
                                管理画面へ →
                            </span>
                        </div>
                    </Link>

                    <!-- Payments Card -->
                    <Link
                        :href="route('payments.index')"
                        class="group block overflow-hidden rounded-lg bg-white p-6 shadow-sm transition hover:shadow-lg dark:bg-gray-800"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    入金
                                </h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    入金記録の管理
                                </p>
                            </div>
                            <div class="rounded-full bg-yellow-100 p-3 dark:bg-yellow-900">
                                <svg class="h-6 w-6 text-yellow-600 dark:text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="mt-4">
                            <span class="text-sm font-medium text-yellow-600 group-hover:underline dark:text-yellow-400">
                                管理画面へ →
                            </span>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
