<script setup>
import { onMounted, ref, watch } from 'vue';
import { useAdmin, useRelationOptions, DEFAULT_CURRENCY } from '../useAdmin';
import { toMajor, toMinor } from '../../shared/format';

/**
 * Per-store menu: availability, price override and stock. Built for quick changes during
 * service (sold out → one tap).
 */
const { api, t, locale, money, errorText } = useAdmin();
const { load } = useRelationOptions(api);

const stores = ref([]);
const storeId = ref(null);
const rows = ref([]);
const error = ref(null);
const savingId = ref(null);
const savedId = ref(null);

async function loadRows() {
    if (!storeId.value) return;
    error.value = null;
    try {
        rows.value = (await api.get(`/admin/stores/${storeId.value}/products`, { per_page: 100 })).data;
    } catch (e) {
        error.value = e;
    }
}

async function update(row, patch) {
    savingId.value = row.product_id;
    error.value = null;
    try {
        const { data } = await api.put(`/admin/stores/${storeId.value}/products/${row.product_id}`, patch);
        Object.assign(row, data);
        savedId.value = row.product_id;
        setTimeout(() => {
            if (savedId.value === row.product_id) savedId.value = null;
        }, 1500);
    } catch (e) {
        error.value = e;
    } finally {
        savingId.value = null;
    }
}

function priceInput(row, value) {
    update(row, { price: value === '' ? null : toMinor(value, DEFAULT_CURRENCY) });
}

function stockInput(row, value) {
    update(row, { stock_quantity: value === '' ? null : Number(value) });
}

watch(storeId, loadRows);
// Product names are translated by the API.
watch(locale, loadRows);
onMounted(async () => {
    try {
        stores.value = await load('stores');
        storeId.value = stores.value[0]?.value ?? null;
    } catch (e) {
        error.value = e;
    }
});
</script>

<template>
    <section>
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <h2 class="text-2xl font-semibold">{{ t('admin.nav.store_products') }}</h2>
            <label class="ms-auto flex items-center gap-2">
                <span class="text-sm">{{ t('admin.fields.store_id') }}</span>
                <select v-model="storeId" class="rounded-lg border bg-white px-3 py-2">
                    <option v-for="s in stores" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>
            </label>
        </div>
        <p v-if="error" class="mb-3 rounded-lg bg-red-50 p-3 text-red-800" role="alert">{{ errorText(error) }}</p>
        <p class="mb-2 text-sm text-stone-500">{{ t('admin.store_products.hint') }}</p>

        <div class="overflow-x-auto rounded-xl bg-white shadow-sm">
            <table class="w-full min-w-[720px]">
                <thead class="bg-stone-50 text-sm text-stone-600">
                    <tr>
                        <th class="px-3 py-2 text-start">{{ t('admin.fields.name') }}</th>
                        <th class="px-3 py-2 text-start">{{ t('admin.fields.is_available') }}</th>
                        <th class="px-3 py-2 text-start">{{ t('admin.fields.base_price') }}</th>
                        <th class="px-3 py-2 text-start">{{ t('admin.fields.store_price') }} ({{ DEFAULT_CURRENCY }})</th>
                        <th class="px-3 py-2 text-start">{{ t('admin.fields.stock_quantity') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!rows.length"><td colspan="5" class="px-3 py-6 text-center text-stone-500">{{ t('admin.empty') }}</td></tr>
                    <tr v-for="row in rows" :key="row.product_id" class="border-t" data-test="store-product">
                        <td class="px-3 py-2">
                            {{ row.name }} <span class="text-xs text-stone-500">{{ row.sku }}</span>
                            <span v-if="savedId === row.product_id" class="ms-2 text-sm text-emerald-700" role="status">✓ {{ t('admin.saved') }}</span>
                        </td>
                        <td class="px-3 py-2">
                            <button
                                class="rounded-full px-3 py-1 text-sm font-semibold"
                                :class="row.is_available ? 'bg-emerald-100 text-emerald-900' : 'bg-red-100 text-red-900'"
                                :disabled="savingId === row.product_id"
                                data-test="toggle-available"
                                @click="update(row, { is_available: !row.is_available })"
                            >
                                {{ row.is_available ? t('admin.store_products.on_sale') : t('admin.store_products.sold_out') }}
                            </button>
                        </td>
                        <td class="px-3 py-2">{{ money(row.base_price) }}</td>
                        <td class="px-3 py-2">
                            <input
                                type="number"
                                min="0"
                                class="w-32 rounded border px-2 py-1"
                                :placeholder="String(toMajor(row.base_price, DEFAULT_CURRENCY))"
                                :value="row.price === null ? '' : toMajor(row.price, DEFAULT_CURRENCY)"
                                :aria-label="t('admin.fields.store_price')"
                                @change="priceInput(row, $event.target.value)"
                            />
                        </td>
                        <td class="px-3 py-2">
                            <input
                                type="number"
                                min="0"
                                class="w-24 rounded border px-2 py-1"
                                :placeholder="t('admin.store_products.unlimited')"
                                :value="row.stock_quantity ?? ''"
                                :aria-label="t('admin.fields.stock_quantity')"
                                @change="stockInput(row, $event.target.value)"
                            />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>
