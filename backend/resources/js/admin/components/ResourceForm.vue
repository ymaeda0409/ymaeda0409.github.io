<script setup>
import { computed } from 'vue';
import { useAdmin } from '../useAdmin';
import FieldInput from './FieldInput.vue';
import TranslationFields from './TranslationFields.vue';
import HoursField from './HoursField.vue';
import OptionGroupsField from './OptionGroupsField.vue';

const props = defineProps({
    resource: { type: Object, required: true },
    modelValue: { type: Object, required: true },
    creating: { type: Boolean, default: false },
    errors: { type: Object, default: () => ({}) },
    readonly: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue']);
const { can, user } = useAdmin();

const fields = computed(() => props.resource.fields.filter((f) => {
    if (f.createOnly && !props.creating) return false;
    if (f.permission && !can(f.permission)) return false;
    if (f.platformOnly && user.value?.franchise_id) return false;
    if (f.showIf && !f.showIf(props.modelValue)) return false;
    return true;
}));

function set(name, value) {
    emit('update:modelValue', { ...props.modelValue, [name]: value });
}

function error(name) {
    return props.errors?.[name]?.[0] ?? null;
}
</script>

<template>
    <div class="grid gap-4 md:grid-cols-2">
        <template v-for="field in fields" :key="field.name">
            <div v-if="field.type === 'translations'" class="md:col-span-2">
                <TranslationFields
                    :model-value="modelValue[field.name] ?? {}"
                    :attributes="field.attributes"
                    :required="field.required ?? []"
                    :multiline="!!field.multiline"
                    :errors="errors"
                    @update:model-value="set(field.name, $event)"
                />
            </div>
            <div v-else-if="field.type === 'hours'" class="md:col-span-2">
                <HoursField :model-value="modelValue[field.name]" @update:model-value="set(field.name, $event)" />
            </div>
            <div v-else-if="field.type === 'option_groups'" class="md:col-span-2">
                <OptionGroupsField :model-value="modelValue[field.name] ?? []" :errors="errors" @update:model-value="set(field.name, $event)" />
            </div>
            <FieldInput
                v-else
                :field="{ ...field, required: field.required || (creating && field.requiredOnCreate) }"
                :model-value="modelValue[field.name]"
                :error="error(field.name)"
                :disabled="readonly"
                @update:model-value="set(field.name, $event)"
            />
        </template>
    </div>
</template>
