<script setup>
import { computed, onMounted, onUnmounted, watch } from 'vue';
import { useAdmin, useLoader } from '../useAdmin';
import { formatDay } from '../../shared/format';
import { statusKey } from '../../shared/i18n';

const { api, t, te, locale, money, number, errorText, can } = useAdmin();
const { state, run } = useLoader(async () => (await api.get('/admin/dashboard')).data);

const d = computed(() => state.data);
const maxDay = computed(() => Math.max(1, ...(d.value?.last_7_days ?? []).map((x) => x.gross_sales)));
const activeTotal = computed(() => Object.values(d.value?.active_orders ?? {}).reduce((a, b) => a + b, 0));

// Product names come translated from the API, so a language switch reloads them.
watch(locale, run);

let timer = null;
onMounted(() => {
    run();
    // Live figures; a slow refresh keeps data usage low on mobile networks.
    timer = setInterval(run, 60_000);
});
onUnmounted(() => clearInterval(timer));
</script>

<template>
    <section>
        <h2 class="mb-4 text-2xl font-semibold">{{ t('admin.nav.dashboard') }}</h2>
        <p v-if="state.error" class="rounded-lg bg-red-50 p-3 text-red-800" role="alert">{{ errorText(state.error) }}</p>

        <template v-if="d">
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div v-if="d.sales_today" class="rounded-xl bg-white p-4 shadow-sm" data-test="card-sales">
                    <p class="text-sm text-stone-500">{{ t('admin.dashboard.sales_today') }}</p>
                    <p class="text-2xl font-semibold">{{ money(d.sales_today.gross_sales, d.currency) }}</p>
                    <p class="text-sm text-stone-500">{{ t('admin.dashboard.completed_orders', { n: number(d.sales_today.orders) }) }}</p>
                </div>
                <div class="rounded-xl bg-white p-4 shadow-sm" data-test="card-orders">
                    <p class="text-sm text-stone-500">{{ t('admin.dashboard.orders_today') }}</p>
                    <p class="text-2xl font-semibold">{{ number(d.orders_today) }}</p>
                    <p class="text-sm text-stone-500">{{ t('admin.dashboard.in_progress', { n: number(activeTotal) }) }}</p>
                </div>
                <div class="rounded-xl bg-white p-4 shadow-sm">
                    <p class="text-sm text-stone-500">{{ t('admin.dashboard.drivers_online') }}</p>
                    <p class="text-2xl font-semibold">{{ number(d.drivers_online) }}</p>
                    <p v-if="d.awaiting_payment" class="text-sm text-amber-700">{{ t('admin.dashboard.awaiting_payment', { n: number(d.awaiting_payment) }) }}</p>
                </div>
                <div class="rounded-xl bg-white p-4 shadow-sm">
                    <p class="text-sm text-stone-500">{{ t('admin.dashboard.stores_open') }}</p>
                    <p class="text-2xl font-semibold">{{ number(d.stores_open) }} / {{ number(d.stores_total) }}</p>
                </div>
            </div>

            <div class="mt-4 grid gap-4 xl:grid-cols-2">
                <div class="rounded-xl bg-white p-4 shadow-sm">
                    <h3 class="mb-3 font-semibold">{{ t('admin.dashboard.active_orders') }}</h3>
                    <ul class="grid gap-2 sm:grid-cols-2">
                        <li v-for="(n, status) in d.active_orders" :key="status" class="flex items-center justify-between rounded-lg bg-stone-50 px-3 py-2">
                            <a :href="`#/orders?status=${status}`" class="hover:underline">{{ t(statusKey(te, status)) }}</a>
                            <span class="font-semibold" :data-test="`active-${status}`">{{ number(n) }}</span>
                        </li>
                    </ul>
                </div>

                <div v-if="d.last_7_days" class="rounded-xl bg-white p-4 shadow-sm">
                    <h3 class="mb-3 font-semibold">{{ t('admin.dashboard.last_7_days') }}</h3>
                    <div class="flex h-40 items-end gap-2" role="img" :aria-label="t('admin.dashboard.last_7_days')">
                        <div v-for="day in d.last_7_days" :key="day.date" class="flex flex-1 flex-col items-center gap-1">
                            <div
                                class="w-full rounded-t bg-emerald-600"
                                :style="{ height: `${Math.max(2, (day.gross_sales / maxDay) * 120)}px` }"
                                :title="`${money(day.gross_sales, d.currency)} · ${day.orders}`"
                            />
                            <span class="text-xs text-stone-500">{{ formatDay(day.date, locale) }}</span>
                        </div>
                    </div>
                </div>

                <div v-if="d.top_products" class="rounded-xl bg-white p-4 shadow-sm xl:col-span-2">
                    <div class="mb-3 flex items-center">
                        <h3 class="font-semibold">{{ t('admin.dashboard.top_products') }}</h3>
                        <a v-if="can('sales.view')" href="#/sales" class="ms-auto text-sm text-emerald-800 hover:underline">{{ t('admin.nav.sales') }} →</a>
                    </div>
                    <p v-if="!d.top_products.length" class="text-stone-500">{{ t('admin.empty') }}</p>
                    <table v-else class="w-full">
                        <tbody>
                            <tr v-for="p in d.top_products" :key="p.key ?? p.label" class="border-t">
                                <td class="py-2">{{ p.label }}</td>
                                <td class="py-2 text-end">{{ t('admin.sales.quantity_n', { n: number(p.quantity) }) }}</td>
                                <td class="py-2 text-end font-semibold">{{ money(p.sales, d.currency) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>
    </section>
</template>
