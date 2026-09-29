<script setup>
import { onMounted, ref, watch } from 'vue';
import { useAdmin } from '../useAdmin';
import { statusKey } from '../../shared/i18n';
import Pager from '../components/Pager.vue';

const props = defineProps({ recordId: { type: String, default: null } });
const { api, t, te, money, number, dateTime, errorText, go, languages } = useAdmin();

const search = ref('');
const rows = ref([]);
const meta = ref(null);
const page = ref(1);
const error = ref(null);
const customer = ref(null);
let debounce = null;

function languageName(code) {
    return languages.value.find((l) => l.code === code)?.native_name ?? code;
}

async function loadList() {
    error.value = null;
    try {
        const res = await api.get('/admin/customers', { search: search.value, page: page.value });
        rows.value = res.data;
        meta.value = res.meta?.pagination ?? null;
    } catch (e) {
        error.value = e;
    }
}

async function loadCustomer() {
    customer.value = null;
    error.value = null;
    try {
        customer.value = (await api.get(`/admin/customers/${props.recordId}`)).data;
    } catch (e) {
        error.value = e;
    }
}

watch(search, () => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        page.value = 1;
        loadList();
    }, 300);
});
watch(page, loadList);
watch(() => props.recordId, (id) => (id ? loadCustomer() : loadList()));
onMounted(() => (props.recordId ? loadCustomer() : loadList()));
</script>

<template>
    <section>
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <a v-if="recordId" href="#/customers" class="rounded-lg bg-white px-3 py-2">← {{ t('admin.back') }}</a>
            <h2 class="text-2xl font-semibold">{{ customer ? (customer.name || customer.phone) : t('admin.nav.customers') }}</h2>
            <input
                v-if="!recordId"
                v-model="search"
                type="search"
                class="ms-auto w-full rounded-lg border px-3 py-2 sm:w-72"
                :placeholder="t('admin.customers.search')"
                data-test="customer-search"
            />
        </div>
        <p v-if="error" class="mb-3 rounded-lg bg-red-50 p-3 text-red-800" role="alert">{{ errorText(error) }}</p>

        <template v-if="!recordId">
            <div class="overflow-x-auto rounded-xl bg-white shadow-sm">
                <table class="w-full min-w-[720px]">
                    <thead class="bg-stone-50 text-sm text-stone-600">
                        <tr>
                            <th class="px-3 py-2 text-start">{{ t('admin.fields.name') }}</th>
                            <th class="px-3 py-2 text-start">{{ t('admin.fields.phone') }}</th>
                            <th class="px-3 py-2 text-start">{{ t('admin.fields.preferred_language') }}</th>
                            <th class="px-3 py-2 text-end">{{ t('admin.fields.orders') }}</th>
                            <th class="px-3 py-2 text-end">{{ t('admin.fields.total_spent') }}</th>
                            <th class="px-3 py-2 text-start">{{ t('admin.fields.last_order_at') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="!rows.length"><td colspan="6" class="px-3 py-6 text-center text-stone-500">{{ t('admin.empty') }}</td></tr>
                        <tr v-for="c in rows" :key="c.id" class="cursor-pointer border-t hover:bg-emerald-50" data-test="customer-row" @click="go('customers', c.id)">
                            <td class="px-3 py-2">{{ c.name || '—' }}</td>
                            <td class="px-3 py-2">{{ c.phone }}</td>
                            <td class="px-3 py-2">{{ languageName(c.preferred_language) }}</td>
                            <td class="px-3 py-2 text-end">{{ number(c.orders_count) }}</td>
                            <td class="px-3 py-2 text-end">{{ money(c.total_spent) }}</td>
                            <td class="px-3 py-2">{{ dateTime(c.last_order_at) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pager :meta="meta" @page="page = $event" />
        </template>

        <div v-else-if="customer" class="grid gap-4 xl:grid-cols-3">
            <dl class="rounded-xl bg-white p-4 shadow-sm">
                <dt class="text-sm text-stone-500">{{ t('admin.fields.phone') }}</dt><dd class="mb-2">{{ customer.phone }}</dd>
                <dt class="text-sm text-stone-500">{{ t('admin.fields.preferred_language') }}</dt><dd class="mb-2">{{ languageName(customer.preferred_language) }}</dd>
                <dt class="text-sm text-stone-500">{{ t('admin.fields.orders') }}</dt><dd class="mb-2">{{ number(customer.orders_count) }}</dd>
                <dt class="text-sm text-stone-500">{{ t('admin.fields.total_spent') }}</dt><dd class="mb-2">{{ money(customer.total_spent) }}</dd>
                <dt class="text-sm text-stone-500">{{ t('admin.fields.created_at') }}</dt><dd>{{ dateTime(customer.created_at) }}</dd>
            </dl>
            <div class="rounded-xl bg-white p-4 shadow-sm xl:col-span-2">
                <h3 class="mb-2 font-semibold">{{ t('admin.customers.recent_orders') }}</h3>
                <table class="w-full">
                    <tbody>
                        <tr v-for="o in customer.recent_orders" :key="o.id" class="cursor-pointer border-t hover:bg-emerald-50" @click="go('orders', o.id)">
                            <td class="py-2 font-mono text-sm">{{ o.order_number }}</td>
                            <td class="py-2">{{ dateTime(o.ordered_at) }}</td>
                            <td class="py-2">{{ t(statusKey(te, o.status)) }}</td>
                            <td class="py-2 text-end">{{ money(o.total, o.currency) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</template>
