<script setup>
import { useAdmin, DEFAULT_CURRENCY } from '../useAdmin';
import { toMajor, toMinor } from '../../shared/format';
import TranslationFields from './TranslationFields.vue';

/**
 * Product option groups (e.g. "Size": Regular / Large +MWK 500) with translated names.
 * Existing ids are sent back so the server updates instead of recreating.
 */
const props = defineProps({
    modelValue: { type: Array, default: () => [] },
    errors: { type: Object, default: () => ({}) },
});
const emit = defineEmits(['update:modelValue']);
const { t } = useAdmin();

function commit(groups) {
    emit('update:modelValue', groups);
}

function updateGroup(index, patch) {
    commit(props.modelValue.map((g, i) => (i === index ? { ...g, ...patch } : g)));
}

function updateOption(gi, oi, patch) {
    const group = props.modelValue[gi];
    updateGroup(gi, { options: group.options.map((o, i) => (i === oi ? { ...o, ...patch } : o)) });
}

function addGroup() {
    commit([...props.modelValue, { min_select: 0, max_select: 1, translations: {}, options: [{ price: 0, is_active: true, translations: {} }] }]);
}

function removeGroup(index) {
    commit(props.modelValue.filter((_, i) => i !== index));
}

function addOption(gi) {
    const group = props.modelValue[gi];
    updateGroup(gi, { options: [...group.options, { price: 0, is_active: true, translations: {} }] });
}

function removeOption(gi, oi) {
    const group = props.modelValue[gi];
    updateGroup(gi, { options: group.options.filter((_, i) => i !== oi) });
}
</script>

<template>
    <fieldset class="rounded-xl border p-3">
        <legend class="px-1 text-sm font-medium text-stone-700">{{ t('admin.fields.option_groups') }}</legend>
        <p v-if="!modelValue.length" class="text-sm text-stone-500">{{ t('admin.options.none') }}</p>

        <div v-for="(group, gi) in modelValue" :key="group.id ?? `new-${gi}`" class="mb-4 rounded-lg bg-stone-50 p-3" data-test="option-group">
            <div class="mb-2 flex flex-wrap items-end gap-3">
                <label class="flex flex-col text-sm">
                    {{ t('admin.fields.min_select') }}
                    <input type="number" min="0" class="w-24 rounded border px-2 py-1" :value="group.min_select" @input="updateGroup(gi, { min_select: Number($event.target.value) })" />
                </label>
                <label class="flex flex-col text-sm">
                    {{ t('admin.fields.max_select') }}
                    <input type="number" min="1" class="w-24 rounded border px-2 py-1" :value="group.max_select" @input="updateGroup(gi, { max_select: Number($event.target.value) })" />
                </label>
                <button type="button" class="ms-auto rounded bg-red-50 px-3 py-1 text-sm text-red-800" @click="removeGroup(gi)">
                    {{ t('admin.options.remove_group') }}
                </button>
            </div>
            <TranslationFields
                :model-value="group.translations"
                :attributes="['name']"
                :required="['name']"
                :errors="errors"
                :prefix="`option_groups.${gi}.translations`"
                @update:model-value="updateGroup(gi, { translations: $event })"
            />

            <div v-for="(option, oi) in group.options" :key="option.id ?? `new-${oi}`" class="mt-2 ms-4 rounded border-s-4 border-emerald-200 bg-white p-2">
                <div class="mb-2 flex flex-wrap items-end gap-3">
                    <label class="flex flex-col text-sm">
                        {{ t('admin.fields.price') }} ({{ DEFAULT_CURRENCY }})
                        <input
                            type="number"
                            min="0"
                            step="0.01"
                            class="w-32 rounded border px-2 py-1"
                            :value="toMajor(option.price ?? 0, DEFAULT_CURRENCY)"
                            @input="updateOption(gi, oi, { price: toMinor($event.target.value || 0, DEFAULT_CURRENCY) })"
                        />
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" :checked="option.is_active !== false" @change="updateOption(gi, oi, { is_active: $event.target.checked })" />
                        {{ t('admin.fields.is_active') }}
                    </label>
                    <button type="button" class="ms-auto rounded px-2 py-1 text-sm text-red-800" @click="removeOption(gi, oi)">
                        {{ t('admin.options.remove_option') }}
                    </button>
                </div>
                <TranslationFields
                    :model-value="option.translations"
                    :attributes="['name']"
                    :required="['name']"
                    :errors="errors"
                    :prefix="`option_groups.${gi}.options.${oi}.translations`"
                    @update:model-value="updateOption(gi, oi, { translations: $event })"
                />
            </div>
            <button type="button" class="mt-2 rounded bg-white px-3 py-1 text-sm" @click="addOption(gi)">+ {{ t('admin.options.add_option') }}</button>
        </div>

        <button type="button" class="rounded-lg bg-emerald-50 px-3 py-2 text-emerald-900" data-test="add-group" @click="addGroup">
            + {{ t('admin.options.add_group') }}
        </button>
    </fieldset>
</template>
