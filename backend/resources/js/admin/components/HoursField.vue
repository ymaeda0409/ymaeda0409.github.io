<script setup>
import { useAdmin } from '../useAdmin';

/**
 * Weekly opening hours, one open–close range per day: { mon: [["08:00","20:00"]], … }.
 * A day without a range is closed.
 */
const props = defineProps({ modelValue: { type: Object, default: null } });
const emit = defineEmits(['update:modelValue']);
const { t } = useAdmin();

const DAYS = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

function range(day) {
    return props.modelValue?.[day]?.[0] ?? null;
}

function set(day, index, value) {
    const next = { ...(props.modelValue ?? {}) };
    const current = range(day) ?? ['08:00', '20:00'];
    const updated = [...current];
    updated[index] = value;
    next[day] = [updated];
    emit('update:modelValue', next);
}

function toggle(day, open) {
    const next = { ...(props.modelValue ?? {}) };
    next[day] = open ? [range(day) ?? ['08:00', '20:00']] : [];
    emit('update:modelValue', next);
}
</script>

<template>
    <fieldset class="rounded-xl border bg-stone-50 p-3">
        <legend class="px-1 text-sm font-medium text-stone-700">{{ t('admin.fields.opening_hours') }}</legend>
        <p v-if="!modelValue" class="mb-2 text-xs text-stone-500">{{ t('admin.hours.always_open') }}</p>
        <div v-for="day in DAYS" :key="day" class="flex flex-wrap items-center gap-2 py-1">
            <label class="flex w-32 items-center gap-2">
                <input type="checkbox" :checked="!!range(day)" @change="toggle(day, $event.target.checked)" />
                {{ t(`admin.hours.days.${day}`) }}
            </label>
            <template v-if="range(day)">
                <input type="time" class="rounded border px-2 py-1" :value="range(day)[0]" :aria-label="t('admin.hours.opens')" @input="set(day, 0, $event.target.value)" />
                <span>–</span>
                <input type="time" class="rounded border px-2 py-1" :value="range(day)[1]" :aria-label="t('admin.hours.closes')" @input="set(day, 1, $event.target.value)" />
            </template>
            <span v-else class="text-sm text-stone-500">{{ t('admin.hours.closed') }}</span>
        </div>
    </fieldset>
</template>
