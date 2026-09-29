<script setup>
import { useAdmin } from '../useAdmin';

defineProps({ meta: { type: Object, default: null } });
const emit = defineEmits(['page']);
const { t } = useAdmin();
</script>

<template>
    <div v-if="meta && meta.last_page > 1" class="mt-3 flex items-center justify-end gap-2">
        <button class="rounded border bg-white px-3 py-1 disabled:opacity-40" :disabled="meta.current_page <= 1" @click="emit('page', meta.current_page - 1)">
            {{ t('admin.previous') }}
        </button>
        <span class="text-sm">{{ t('admin.page_of', { page: meta.current_page, total: meta.last_page }) }}</span>
        <button class="rounded border bg-white px-3 py-1 disabled:opacity-40" :disabled="meta.current_page >= meta.last_page" @click="emit('page', meta.current_page + 1)">
            {{ t('admin.next') }}
        </button>
    </div>
</template>
