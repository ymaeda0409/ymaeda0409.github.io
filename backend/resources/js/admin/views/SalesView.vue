<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useAdmin, useRelationOptions, saveBlob } from '../useAdmin';
import { addDays, formatDay, todayIn } from '../../shared/format';

const GROUPS = ['day', 'store', 'franchise', 'product', 'payment_method'];
const TIMEZONE = 'Africa/Blantyre';

const { api, t, locale, money, number, errorText, enumText, can, user } = useAdmin();
const { load } = useRelationOptions(api);

const today = todayIn(TIMEZONE);
const from = ref(addDays(today, -6));
const to = ref(today);
const groupBy = ref('day');
const franchiseId = ref('');
const storeId = ref('');
const report = ref(null);
const error = ref(null);
const loading = ref(false);
const franchises = ref([]);
const stores = ref([]);

const params = computed(() => ({
    from: from.value,
    to: to.value,
    group_by: groupBy.value,
    franchise_id: franchiseId.value || null,
    store_id: storeId.value || null,
}));
const isProduct = computed(() => groupBy.value === 'product');
const isFranchise = computed(() => groupBy.value === 'franchise');
const currency = computed(() => report.value?.currency ?? 'MWK');
const maxValue = computed(() => Math.max(1, ...(report.value?.rows ?? []).map((r) => r.gross_sales ?? r.sales ?? 0)));

function presetDays(days) {
    to.value = today;
    from.value = addDays(today, -(days - 1));
}

function rowLabel(row) {
    if (groupBy.value === 'day') return formatDay(row.key, locale.value);
    if (groupBy.value === 'payment_method') return enumText('payment_method', row.key);
    return row.label;
}

async function fetchReport() {
    loading.value = true;
    error.value = null;
    try {
        report.value = (await api.get('/admin/sales', params.value)).data;
    } catch (e) {
        error.value = e;
    } finally {
        loading.value = false;
    }
}

async function exportCsv() {
    try {
        saveBlob(await api.download('/admin/sales', { ...params.value, format: 'csv' }));
    } catch (e) {
        error.value = e;
    }
}

watch(params, fetchReport);
// Product names are translated by the API.
watch(locale, fetchReport);
onMounted(async () => {
    fetchReport();
    // Filters only offer what this user can see (the API rejects anything else).
    if (!user.value?.franchise_id && can('franchises.view')) franchises.value = await load('franchises').catch(() => []);
    if (!user.value?.store_id) stores.value = await load('stores').catch(() => []);
});
</script>

<template>
    <section>
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <h2 class="text-2xl font-semibold">{{ t('admin.nav.sales') }}</h2>
            <button class="ms-auto rounded-lg bg-white px-4 py-2 shadow-sm" data-test="export" @click="exportCsv">⬇ {{ t('admin.sales.export_csv') }}</button>
        </div>

        <div class="mb-4 flex flex-wrap items-end gap-3 rounded-xl bg-white p-4 shadow-sm">
            <label class="flex flex-col text-sm">{{ t('admin.sales.from') }}<input v-model="from" type="date" class="rounded border px-2 py-1" :max="to" data-test="from" /></label>
            <label class="flex flex-col text-sm">{{ t('admin.sales.to') }}<input v-model="to" type="date" class="rounded border px-2 py-1" :min="from" :max="today" /></label>
            <div class="flex gap-1">
                <button v-for="n in [7, 30, 90]" :key="n" class="rounded bg-stone-100 px-2 py-1 text-sm" @click="presetDays(n)">{{ t('admin.sales.last_n_days', { n }) }}</button>
            </div>
            <label class="flex flex-col text-sm">
                {{ t('admin.sales.group_by') }}
                <select v-model="groupBy" class="rounded border bg-white px-2 py-1" data-test="group-by">
                    <option v-for="g in GROUPS" :key="g" :value="g">{{ t(`admin.sales.groups.${g}`) }}</option>
                </select>
            </label>
            <label v-if="franchises.length" class="flex flex-col text-sm">
                {{ t('admin.fields.franchise_id') }}
                <select v-model="franchiseId" class="rounded border bg-white px-2 py-1">
                    <option value="">{{ t('admin.all') }}</option>
                    <option v-for="f in franchises" :key="f.value" :value="f.value">{{ f.label }}</option>
                </select>
            </label>
            <label v-if="stores.length > 1" class="flex flex-col text-sm">
                {{ t('admin.fields.store_id') }}
                <select v-model="storeId" class="rounded border bg-white px-2 py-1">
                    <option value="">{{ t('admin.all') }}</option>
                    <option v-for="s in stores" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>
            </label>
        </div>

        <p v-if="error" class="mb-3 rounded-lg bg-red-50 p-3 text-red-800" role="alert">{{ errorText(error) }}</p>

        <template v-if="report">
            <div class="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                <div class="rounded-xl bg-white p-4 shadow-sm" data-test="summary-gross">
                    <p class="text-sm text-stone-500">{{ t('admin.fields.gross_sales') }}</p>
                    <p class="text-xl font-semibold">{{ money(report.summary.gross_sales, currency) }}</p>
                </div>
                <div class="rounded-xl bg-white p-4 shadow-sm">
                    <p class="text-sm text-stone-500">{{ t('admin.fields.orders') }}</p>
                    <p class="text-xl font-semibold" data-test="summary-orders">{{ number(report.summary.orders) }}</p>
                </div>
                <div class="rounded-xl bg-white p-4 shadow-sm">
                    <p class="text-sm text-stone-500">{{ t('admin.fields.average_order_value') }}</p>
                    <p class="text-xl font-semibold">{{ money(report.summary.average_order_value, currency) }}</p>
                </div>
                <div class="rounded-xl bg-white p-4 shadow-sm">
                    <p class="text-sm text-stone-500">{{ t('admin.fields.delivery_fees') }}</p>
                    <p class="text-xl font-semibold">{{ money(report.summary.delivery_fees, currency) }}</p>
                </div>
                <div class="rounded-xl bg-white p-4 shadow-sm">
                    <p class="text-sm text-stone-500">{{ t('admin.sales.cancelled') }}</p>
                    <p class="text-xl font-semibold">{{ number(report.summary.cancelled) }}</p>
                </div>
            </div>

            <div class="overflow-x-auto rounded-xl bg-white shadow-sm">
                <table class="w-full min-w-[640px]" data-test="sales-table">
                    <thead class="bg-stone-50 text-sm text-stone-600">
                        <tr>
                            <th class="px-3 py-2 text-start">{{ t(`admin.sales.groups.${groupBy}`) }}</th>
                            <template v-if="isProduct">
                                <th class="px-3 py-2 text-end">{{ t('admin.fields.quantity') }}</th>
                                <th class="px-3 py-2 text-end">{{ t('admin.fields.orders') }}</th>
                                <th class="px-3 py-2 text-end">{{ t('admin.fields.sales') }}</th>
                            </template>
                            <template v-else>
                                <th class="px-3 py-2 text-end">{{ t('admin.fields.orders') }}</th>
                                <th class="px-3 py-2 text-end">{{ t('admin.fields.subtotal') }}</th>
                                <th class="px-3 py-2 text-end">{{ t('admin.fields.delivery_fees') }}</th>
                                <th class="px-3 py-2 text-end">{{ t('admin.fields.gross_sales') }}</th>
                                <th v-if="isFranchise" class="px-3 py-2 text-end">{{ t('admin.fields.commission') }}</th>
                            </template>
                            <th class="w-1/4 px-3 py-2"><span class="sr-only">{{ t('admin.sales.share') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="!report.rows.length"><td colspan="7" class="px-3 py-6 text-center text-stone-500">{{ t('admin.empty') }}</td></tr>
                        <tr v-for="row in report.rows" :key="row.key ?? row.label" class="border-t" data-test="sales-row">
                            <td class="px-3 py-2">{{ rowLabel(row) }}</td>
                            <template v-if="isProduct">
                                <td class="px-3 py-2 text-end">{{ number(row.quantity) }}</td>
                                <td class="px-3 py-2 text-end">{{ number(row.orders) }}</td>
                                <td class="px-3 py-2 text-end font-semibold">{{ money(row.sales, currency) }}</td>
                            </template>
                            <template v-else>
                                <td class="px-3 py-2 text-end">{{ number(row.orders) }}</td>
                                <td class="px-3 py-2 text-end">{{ money(row.subtotal, currency) }}</td>
                                <td class="px-3 py-2 text-end">{{ money(row.delivery_fees, currency) }}</td>
                                <td class="px-3 py-2 text-end font-semibold">{{ money(row.gross_sales, currency) }}</td>
                                <td v-if="isFranchise" class="px-3 py-2 text-end">
                                    {{ money(row.commission, currency) }}
                                    <span class="block text-xs text-stone-500">{{ number(row.commission_rate / 100, { style: 'percent', maximumFractionDigits: 2 }) }}</span>
                                </td>
                            </template>
                            <td class="px-3 py-2">
                                <div class="h-2 rounded bg-emerald-500" :style="{ width: `${((row.gross_sales ?? row.sales) / maxValue) * 100}%` }" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="mt-2 text-xs text-stone-500">{{ t('admin.sales.definition', { timezone: report.timezone }) }}</p>
        </template>
    </section>
</template>
