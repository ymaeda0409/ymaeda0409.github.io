<script setup>
import { computed, onMounted, ref } from 'vue';
import { useAdmin, useRelationOptions, DEFAULT_CURRENCY } from '../useAdmin';
import { MANAGEABLE_ROLES } from '../resources';
import { exponentOf, toMajor, toMinor } from '../../shared/format';

const props = defineProps({
    field: { type: Object, required: true },
    modelValue: { type: null, default: null },
    error: { type: String, default: null },
    disabled: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue']);

const { t, api, user, languages, enumText } = useAdmin();
const { load, label: relationLabel } = useRelationOptions(api);
const options = ref([]);
const id = `f-${props.field.name}-${Math.random().toString(36).slice(2, 8)}`;

const choices = computed(() => {
    const f = props.field;
    if (f.type === 'select') return f.options.map((v) => ({ value: v, label: enumText(f.enum, v) }));
    if (f.type === 'role') return (MANAGEABLE_ROLES[user.value?.role] ?? []).map((v) => ({ value: v, label: enumText('role', v) }));
    if (f.type === 'language') return languages.value.map((l) => ({ value: l.code, label: l.native_name ?? l.name }));
    return options.value.map((o) => ({ value: o.value, label: relationLabel(o.row) }));
});

const moneyValue = computed({
    get: () => (props.modelValue === null || props.modelValue === undefined || props.modelValue === '' ? '' : toMajor(props.modelValue, DEFAULT_CURRENCY)),
    set: (v) => emit('update:modelValue', v === '' ? null : toMinor(v, DEFAULT_CURRENCY)),
});

const inputType = computed(() => ({ number: 'number', decimal: 'number', email: 'email', tel: 'tel', password: 'password' })[props.field.type] ?? 'text');

onMounted(async () => {
    if (props.field.type === 'relation') {
        try {
            options.value = await load(props.field.source);
        } catch {
            options.value = [];
        }
    }
});
</script>

<template>
    <div class="flex flex-col gap-1">
        <label :for="id" class="text-sm font-medium text-stone-700">
            {{ t(`admin.fields.${field.name}`) }}
            <span v-if="field.required" aria-hidden="true" class="text-red-700">*</span>
        </label>

        <label v-if="field.type === 'boolean'" class="inline-flex items-center gap-2">
            <input
                :id="id"
                type="checkbox"
                class="h-5 w-5"
                :checked="!!modelValue"
                :disabled="disabled"
                @change="emit('update:modelValue', $event.target.checked)"
            />
            <span>{{ modelValue ? t('admin.yes') : t('admin.no') }}</span>
        </label>

        <select
            v-else-if="['select', 'relation', 'role', 'language'].includes(field.type)"
            :id="id"
            class="rounded-lg border bg-white px-3 py-2"
            :value="modelValue ?? ''"
            :disabled="disabled"
            @change="emit('update:modelValue', $event.target.value === '' ? null : $event.target.value)"
        >
            <option value="">{{ t('admin.choose') }}</option>
            <option v-for="c in choices" :key="c.value" :value="c.value">{{ c.label }}</option>
        </select>

        <div v-else-if="field.type === 'money'" class="flex items-center gap-2">
            <input
                :id="id"
                v-model="moneyValue"
                type="number"
                min="0"
                :step="1 / 10 ** exponentOf(DEFAULT_CURRENCY)"
                class="w-full rounded-lg border px-3 py-2"
                :disabled="disabled"
            />
            <span class="text-sm text-stone-500">{{ DEFAULT_CURRENCY }}</span>
        </div>

        <input
            v-else
            :id="id"
            :type="inputType"
            :step="field.step"
            class="rounded-lg border px-3 py-2"
            :value="modelValue ?? ''"
            :disabled="disabled"
            :autocomplete="field.type === 'password' ? 'new-password' : 'off'"
            @input="emit('update:modelValue', $event.target.value)"
        />

        <p v-if="error" class="text-sm text-red-700" role="alert">{{ error }}</p>
    </div>
</template>
