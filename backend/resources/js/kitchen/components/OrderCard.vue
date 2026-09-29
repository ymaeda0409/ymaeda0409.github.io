<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { formattingLocale, statusKey } from '../../shared/i18n';

const props = defineProps({
    order: { type: Object, required: true },
    now: { type: Number, required: true },
    busy: { type: Boolean, default: false },
});
const emit = defineEmits(['action']);
const { t, te, locale } = useI18n();

/** Primary kitchen action per status (NEW → ACCEPT, CONFIRMED → START COOKING, COOKING → READY). */
const primary = computed(() => {
    switch (props.order.status) {
        case 'NEW':
            return { action: 'accept', label: 'kitchen.accept', disabled: !props.order.is_payable };
        case 'CONFIRMED':
            return { action: 'start-cooking', label: 'kitchen.start_cooking' };
        case 'COOKING':
            return { action: 'ready', label: 'kitchen.ready' };
        default:
            return null;
    }
});

const cancellable = computed(() => ['NEW', 'CONFIRMED', 'COOKING'].includes(props.order.status));
const minutes = computed(() => Math.max(0, Math.floor((props.now - Date.parse(props.order.ordered_at)) / 60000)));
const itemCount = computed(() => props.order.items.reduce((sum, i) => sum + i.quantity, 0));
const scheduled = computed(() =>
    props.order.scheduled_at
        ? new Intl.DateTimeFormat(formattingLocale(locale.value), { dateStyle: 'short', timeStyle: 'short' }).format(new Date(props.order.scheduled_at))
        : null,
);
const note = computed(() => props.order.delivery_address?.delivery_note);

function cancel() {
    if (window.confirm(t('kitchen.cancel_confirm', { number: props.order.order_number }))) {
        emit('action', 'cancel');
    }
}
</script>

<template>
    <article class="space-y-3 rounded-2xl bg-white p-4 shadow-sm" :data-status="order.status">
        <header class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
            <h3 class="text-lg font-bold break-all">{{ order.order_number }}</h3>
            <span class="text-stone-500">{{ t('kitchen.minutes_ago', { n: minutes }, minutes) }}</span>
            <span class="ms-auto rounded-full bg-stone-100 px-2 py-0.5 text-sm">{{ t(statusKey(te, order.status)) }}</span>
        </header>

        <p v-if="scheduled" class="font-semibold text-amber-700">{{ t('kitchen.scheduled_for', { time: scheduled }) }}</p>

        <ul class="space-y-1">
            <li v-for="(item, index) in order.items" :key="index" class="text-lg">
                <span class="font-bold">{{ item.quantity }}×</span>
                {{ item.name }}
                <span v-if="item.options.length" class="block ps-6 text-base text-stone-600">
                    {{ item.options.map((o) => o.name).join(' / ') }}
                </span>
            </li>
        </ul>
        <p class="text-sm text-stone-500">{{ t('kitchen.items', { n: itemCount }, itemCount) }}</p>
        <p v-if="note" class="rounded-lg bg-amber-50 p-2 text-sm">
            <span class="font-semibold">{{ t('kitchen.note') }}:</span> {{ note }}
        </p>

        <div class="flex flex-wrap items-center gap-2 text-sm">
            <span class="rounded-full bg-stone-100 px-2 py-0.5">{{ t(`payment.${order.payment_method}`) }}</span>
            <span v-if="order.payment_status === 'PAID'" class="rounded-full bg-emerald-100 px-2 py-0.5">{{ t('payment.paid') }}</span>
            <span v-if="!order.is_payable" class="rounded-full bg-red-100 px-2 py-0.5 text-red-800">{{ t('kitchen.awaiting_payment') }}</span>
            <span v-if="order.status === 'READY_FOR_PICKUP'" class="rounded-full bg-sky-100 px-2 py-0.5">{{ t('kitchen.waiting_rider') }}</span>
        </div>

        <button
            v-if="primary"
            type="button"
            class="w-full rounded-xl bg-emerald-700 px-4 py-4 text-lg font-bold text-white hover:bg-emerald-600 disabled:opacity-40"
            :disabled="busy || primary.disabled"
            :data-action="primary.action"
            @click="emit('action', primary.action)"
        >
            {{ t(primary.label) }}
        </button>
        <button v-if="cancellable" type="button" class="text-sm text-red-700 underline" :disabled="busy" data-action="cancel" @click="cancel">
            {{ t('kitchen.cancel_order') }}
        </button>
    </article>
</template>
