<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { errorKey, formattingLocale } from '../../shared/i18n';
import OrderCard from './OrderCard.vue';

const props = defineProps({
    api: { type: Object, required: true },
    pollMs: { type: Number, default: 10000 },
});

const { t, te, locale } = useI18n();
const orders = ref([]);
const error = ref(null);
const offline = ref(false);
const busyId = ref(null);
const updatedAt = ref(null);
const now = ref(Date.now());
const newAlert = ref(false);
let seen = null;
let timer = null;
let clock = null;

const columns = computed(() => [
    { key: 'new', title: 'kitchen.column_new', statuses: ['NEW', 'CONFIRMED'] },
    { key: 'cooking', title: 'kitchen.column_cooking', statuses: ['COOKING'] },
    { key: 'ready', title: 'kitchen.column_ready', statuses: ['READY_FOR_PICKUP'] },
].map((c) => ({ ...c, orders: orders.value.filter((o) => c.statuses.includes(o.status)) })));

const updatedLabel = computed(() =>
    updatedAt.value ? new Intl.DateTimeFormat(formattingLocale(locale.value), { timeStyle: 'short' }).format(updatedAt.value) : '',
);

function beep() {
    try {
        const ctx = new AudioContext();
        const osc = ctx.createOscillator();
        osc.connect(ctx.destination);
        osc.frequency.value = 880;
        osc.start();
        osc.stop(ctx.currentTime + 0.3);
    } catch {
        // Audio may be blocked until the user interacts with the page.
    }
}

async function load() {
    try {
        const { data } = await props.api.get('/admin/orders?board=kitchen');
        const ids = new Set(data.map((o) => o.id));
        if (seen && data.some((o) => !seen.has(o.id) && o.status === 'NEW')) {
            newAlert.value = true;
            beep();
        }
        seen = ids;
        orders.value = data;
        offline.value = false;
        updatedAt.value = new Date();
    } catch (e) {
        offline.value = e.code === 'NETWORK';
        if (!offline.value) error.value = e.code;
    }
}

async function act(order, action) {
    busyId.value = order.id;
    error.value = null;
    newAlert.value = false;
    try {
        const body = action === 'cancel' ? { reason_code: 'STORE_CANCELLED' } : {};
        const { data } = await props.api.post(`/admin/orders/${order.id}/${action}`, body);
        orders.value = orders.value.map((o) => (o.id === data.id ? data : o)).filter((o) => o.status !== 'CANCELLED');
    } catch (e) {
        error.value = e.code;
        await load();
    } finally {
        busyId.value = null;
    }
}

// Item names come from the API in the staff member's language: refetch right away on switch.
watch(locale, load);

onMounted(() => {
    load();
    timer = setInterval(load, props.pollMs);
    clock = setInterval(() => (now.value = Date.now()), 30000);
});
onUnmounted(() => {
    clearInterval(timer);
    clearInterval(clock);
});
</script>

<template>
    <div class="space-y-3">
        <div class="flex flex-wrap items-center gap-3 text-sm text-stone-600">
            <span v-if="updatedAt">{{ t('common.last_updated', { time: updatedLabel }) }}</span>
            <span v-if="offline" class="rounded bg-amber-100 px-2 py-1 text-amber-900">{{ t('common.offline') }}</span>
            <span v-if="newAlert" role="status" class="rounded bg-emerald-100 px-2 py-1 font-semibold text-emerald-900">{{ t('kitchen.new_order_alert') }}</span>
        </div>
        <p v-if="error" role="alert" class="rounded-lg bg-red-50 p-3 text-red-800">{{ t(errorKey(te, error)) }}</p>

        <div class="grid gap-4 md:grid-cols-3">
            <section v-for="column in columns" :key="column.key" class="space-y-3" :data-column="column.key">
                <h2 class="flex items-center gap-2 text-lg font-bold tracking-wide">
                    {{ t(column.title) }}
                    <span class="rounded-full bg-stone-800 px-2 text-sm text-white">{{ column.orders.length }}</span>
                </h2>
                <p v-if="!column.orders.length" class="rounded-2xl border-2 border-dashed border-stone-300 p-6 text-center text-stone-500">
                    {{ t('kitchen.empty') }}
                </p>
                <OrderCard
                    v-for="order in column.orders"
                    :key="order.id"
                    :order="order"
                    :now="now"
                    :busy="busyId === order.id"
                    @action="(a) => act(order, a)"
                />
            </section>
        </div>
    </div>
</template>
