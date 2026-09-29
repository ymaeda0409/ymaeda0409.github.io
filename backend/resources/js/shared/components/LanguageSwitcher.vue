<script setup>
import { useI18n } from 'vue-i18n';
import { messages } from '../i18n';

defineProps({ modelValue: { type: String, required: true } });
defineEmits(['update:modelValue']);

const { t } = useI18n();
// Each language is listed by its own name, read from its own message file.
const options = Object.entries(messages).map(([code, m]) => ({ code, name: m.language_name }));
</script>

<template>
    <label class="flex items-center gap-2">
        <span class="sr-only">{{ t('common.language') }}</span>
        <select
            class="rounded-lg bg-emerald-700 px-3 py-2 text-white"
            :value="modelValue"
            @change="$emit('update:modelValue', $event.target.value)"
        >
            <option v-for="o in options" :key="o.code" :value="o.code">{{ o.name }}</option>
        </select>
    </label>
</template>
