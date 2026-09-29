<script setup>
import { computed, ref } from 'vue';
import { useAdmin } from '../useAdmin';

/**
 * One tab per active language (from the API, so a new language appears without code
 * changes). The default language is marked; other languages fall back to it when empty.
 */
const props = defineProps({
    modelValue: { type: Object, default: () => ({}) },
    attributes: { type: Array, required: true },
    required: { type: Array, default: () => [] },
    multiline: { type: Boolean, default: false },
    errors: { type: Object, default: () => ({}) },
    prefix: { type: String, default: 'translations' },
});
const emit = defineEmits(['update:modelValue']);

const { t, languages } = useAdmin();
const tabs = computed(() => (languages.value.length ? languages.value : [{ code: 'en', native_name: 'English', is_default: true }]));
const active = ref(null);
const current = computed(() => active.value ?? tabs.value.find((l) => l.is_default)?.code ?? tabs.value[0].code);

function isDefault(code) {
    return tabs.value.find((l) => l.code === code)?.is_default ?? code === 'en';
}

function filled(code) {
    const values = props.modelValue?.[code];
    return !!values && props.attributes.some((a) => values[a]);
}

function update(code, attribute, value) {
    const next = { ...(props.modelValue ?? {}) };
    next[code] = { ...(next[code] ?? {}), [attribute]: value === '' ? null : value };
    emit('update:modelValue', next);
}

function fieldError(code, attribute) {
    return props.errors?.[`${props.prefix}.${code}.${attribute}`]?.[0] ?? null;
}
</script>

<template>
    <div class="rounded-xl border bg-stone-50 p-3">
        <div class="mb-3 flex flex-wrap gap-1" role="tablist">
            <button
                v-for="lang in tabs"
                :key="lang.code"
                type="button"
                role="tab"
                :aria-selected="current === lang.code"
                class="rounded-lg px-3 py-1 text-sm"
                :class="current === lang.code ? 'bg-emerald-700 text-white' : 'bg-white'"
                :data-test="`tab-${lang.code}`"
                @click="active = lang.code"
            >
                {{ lang.native_name ?? lang.code }}
                <span v-if="isDefault(lang.code)" class="text-xs">({{ t('admin.translations.default') }})</span>
                <span v-else-if="!filled(lang.code)" class="text-xs text-amber-600" :title="t('admin.translations.missing')">●</span>
            </button>
        </div>

        <div v-for="attribute in attributes" :key="attribute" class="mb-3 flex flex-col gap-1">
            <label :for="`${prefix}-${current}-${attribute}`" class="text-sm font-medium text-stone-700">
                {{ t(`admin.fields.${attribute}`) }} ({{ current }})
                <span v-if="required.includes(attribute) && isDefault(current)" aria-hidden="true" class="text-red-700">*</span>
            </label>
            <textarea
                v-if="multiline && attribute !== 'name'"
                :id="`${prefix}-${current}-${attribute}`"
                rows="3"
                class="rounded-lg border px-3 py-2"
                :value="modelValue?.[current]?.[attribute] ?? ''"
                @input="update(current, attribute, $event.target.value)"
            />
            <input
                v-else
                :id="`${prefix}-${current}-${attribute}`"
                class="rounded-lg border px-3 py-2"
                :value="modelValue?.[current]?.[attribute] ?? ''"
                :data-test="`input-${current}-${attribute}`"
                @input="update(current, attribute, $event.target.value)"
            />
            <p v-if="!isDefault(current) && !modelValue?.[current]?.[attribute]" class="text-xs text-stone-500">
                {{ t('admin.translations.fallback_hint') }}
            </p>
            <p v-if="fieldError(current, attribute)" class="text-sm text-red-700" role="alert">{{ fieldError(current, attribute) }}</p>
        </div>
    </div>
</template>
