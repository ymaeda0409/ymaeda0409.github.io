<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useAdmin } from '../useAdmin';
import { statusKey } from '../../shared/i18n';
import Pager from '../components/Pager.vue';

const STATUSES = ['NEW', 'CONFIRMED', 'COOKING', 'READY_FOR_PICKUP', 'RIDER_ASSIGNED', 'PICKED_UP', 'ON_THE_WAY', 'ARRIVED', 'DELIVERED', 'CANCELLED', 'FAILED_DELIVERY'];
const CANCELLABLE = ['NEW', 'CONFIRMED', 'COOKING', 'READY_FOR_PICKUP'];

const props = defineProps({ recordId: { type: String, default: null } });
const { api, t, te, locale, money, dateTime, errorText, enumText, can, go, route } = useAdmin();

const status = ref(route?.query?.status ?? '');
const rows = ref([]);
const meta = ref(null);
const page = ref(1);
const error = ref(null);
const order = ref(null);
const busy = ref(false);

const orderId = computed(() => props.recordId || null);

async function loadList() {
    error.value = null;
    try {
        const res = await api.get('/admin/orders', { status: status.value ? [status.value] : null, page: page.value });
        rows.value = res.data;
        meta.value = res.meta?.pagination ?? null;
    } catch (e) {
        error.value = e;
    }
}

async function loadOrder() {
    error.value = null;
    order.value = null;
    if (!orderId.value) return;
    try {
        order.value = (await api.get(`/admin/orders/${orderId.value}`)).data;
    } catch (e) {
        error.value = e;
    }
}

async function cancel() {
    if (!window.confirm(t('admin.orders.cancel_confirm', { number: order.value.order_number }))) return;
    busy.value = true;
    try {
        order.value = (await api.post(`/admin/orders/${order.value.id}/cancel`, { reason_code: 'STORE_CANCELLED' })).data;
        await loadOrder();
    } catch (e) {
        error.value = e;
    } finally {
        busy.value = false;
    }
}

watch([status, page], loadList);
// Item names are translated by the API.
watch(locale, () => (orderId.value ? loadOrder() : loadList()));
watch(orderId, (id) => (id ? loadOrder() : loadList()));
onMounted(() => (orderId.value ? loadOrder() : loadList()));
</script>

<template>
    <section>
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <a v-if="orderId" href="#/orders" class="rounded-lg bg-white px-3 py-2">← {{ t('admin.back') }}</a>
            <h2 class="text-2xl font-semibold">{{ order ? order.order_number : t('admin.nav.orders') }}</h2>
            <label v-if="!orderId" class="ms-auto flex items-center gap-2">
                <span class="text-sm">{{ t('admin.fields.status') }}</span>
                <select v-model="status" class="rounded-lg border bg-white px-3 py-2" data-test="status-filter" @change="page = 1">
                    <option value="">{{ t('admin.all') }}</option>
                    <option v-for="s in STATUSES" :key="s" :value="s">{{ t(statusKey(te, s)) }}</option>
                </select>
            </label>
        </div>
        <p v-if="error" class="mb-3 rounded-lg bg-red-50 p-3 text-red-800" role="alert">{{ errorText(error) }}</p>

        <template v-if="!orderId">
            <div class="overflow-x-auto rounded-xl bg-white shadow-sm">
                <table class="w-full min-w-[720px]">
                    <thead class="bg-stone-50 text-sm text-stone-600">
                        <tr>
                            <th class="px-3 py-2 text-start">{{ t('admin.fields.order_number') }}</th>
                            <th class="px-3 py-2 text-start">{{ t('admin.fields.ordered_at') }}</th>
                            <th class="px-3 py-2 text-start">{{ t('admin.fields.customer') }}</th>
                            <th class="px-3 py-2 text-start">{{ t('admin.fields.status') }}</th>
                            <th class="px-3 py-2 text-start">{{ t('admin.fields.payment') }}</th>
                            <th class="px-3 py-2 text-end">{{ t('admin.fields.total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="!rows.length"><td colspan="6" class="px-3 py-6 text-center text-stone-500">{{ t('admin.empty') }}</td></tr>
                        <tr v-for="o in rows" :key="o.id" class="cursor-pointer border-t hover:bg-emerald-50" data-test="order-row" @click="go('orders', o.id)">
                            <td class="px-3 py-2 font-mono text-sm">{{ o.order_number }}</td>
                            <td class="px-3 py-2">{{ dateTime(o.ordered_at) }}</td>
                            <td class="px-3 py-2">{{ o.customer?.name ?? o.customer?.phone }}</td>
                            <td class="px-3 py-2">{{ t(statusKey(te, o.status)) }}</td>
                            <td class="px-3 py-2">{{ enumText('payment_method', o.payment_method) }} · {{ enumText('payment_status', o.payment_status) }}</td>
                            <td class="px-3 py-2 text-end">{{ money(o.total, o.currency) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pager :meta="meta" @page="page = $event" />
        </template>

        <div v-else-if="order" class="grid gap-4 xl:grid-cols-3">
            <div class="rounded-xl bg-white p-4 shadow-sm xl:col-span-2">
                <dl class="grid gap-2 sm:grid-cols-2">
                    <div><dt class="text-sm text-stone-500">{{ t('admin.fields.status') }}</dt><dd class="font-semibold" data-test="order-status">{{ t(statusKey(te, order.status)) }}</dd></div>
                    <div><dt class="text-sm text-stone-500">{{ t('admin.fields.payment') }}</dt><dd>{{ enumText('payment_method', order.payment_method) }} · {{ enumText('payment_status', order.payment_status) }}</dd></div>
                    <div><dt class="text-sm text-stone-500">{{ t('admin.fields.customer') }}</dt><dd>{{ order.customer?.name }} {{ order.customer?.phone }}</dd></div>
                    <div><dt class="text-sm text-stone-500">{{ t('admin.fields.ordered_at') }}</dt><dd>{{ dateTime(order.ordered_at) }}</dd></div>
                    <div class="sm:col-span-2">
                        <dt class="text-sm text-stone-500">{{ t('admin.fields.address') }}</dt>
                        <dd>{{ [order.delivery_address?.area, order.delivery_address?.landmark, order.delivery_address?.note].filter(Boolean).join(' · ') }}</dd>
                    </div>
                </dl>
                <table class="mt-4 w-full">
                    <tbody>
                        <tr v-for="(item, i) in order.items" :key="i" class="border-t">
                            <td class="py-2">
                                {{ item.quantity }} × {{ item.name }}
                                <span v-if="item.options.length" class="block text-sm text-stone-500">{{ item.options.map((o) => o.name).join(', ') }}</span>
                            </td>
                            <td class="py-2 text-end">{{ money(item.total, order.currency) }}</td>
                        </tr>
                        <tr class="border-t text-sm text-stone-600"><td class="py-1">{{ t('admin.fields.subtotal') }}</td><td class="text-end">{{ money(order.subtotal, order.currency) }}</td></tr>
                        <tr class="text-sm text-stone-600"><td class="py-1">{{ t('admin.fields.delivery_fee') }}</td><td class="text-end">{{ money(order.delivery_fee, order.currency) }}</td></tr>
                        <tr v-if="order.service_fee" class="text-sm text-stone-600"><td class="py-1">{{ t('admin.fields.service_fee') }}</td><td class="text-end">{{ money(order.service_fee, order.currency) }}</td></tr>
                        <tr class="border-t font-semibold"><td class="py-2">{{ t('admin.fields.total') }}</td><td class="text-end">{{ money(order.total, order.currency) }}</td></tr>
                    </tbody>
                </table>
                <button
                    v-if="can('kitchen.operate') && CANCELLABLE.includes(order.status)"
                    class="mt-4 rounded-lg bg-red-50 px-4 py-2 text-red-800 disabled:opacity-50"
                    :disabled="busy"
                    data-test="cancel-order"
                    @click="cancel"
                >
                    {{ t('admin.orders.cancel') }}
                </button>
            </div>
            <div class="rounded-xl bg-white p-4 shadow-sm">
                <h3 class="mb-2 font-semibold">{{ t('admin.orders.timeline') }}</h3>
                <ol class="space-y-2">
                    <li v-for="(step, i) in order.timeline" :key="i" class="border-s-4 border-emerald-500 ps-3">
                        <p class="font-medium">{{ t(statusKey(te, step.status)) }}</p>
                        <p class="text-sm text-stone-500">{{ dateTime(step.at) }}</p>
                    </li>
                </ol>
            </div>
        </div>
    </section>
</template>
