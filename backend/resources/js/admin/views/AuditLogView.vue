<script setup>
import { onMounted, ref, watch } from 'vue';
import { useAdmin } from '../useAdmin';
import Pager from '../components/Pager.vue';

const { api, t, dateTime, errorText } = useAdmin();
const rows = ref([]);
const meta = ref(null);
const page = ref(1);
const error = ref(null);
const open = ref(null);

async function load() {
    error.value = null;
    try {
        const res = await api.get('/admin/audit-logs', { page: page.value });
        rows.value = res.data;
        meta.value = res.meta?.pagination ?? null;
    } catch (e) {
        error.value = e;
    }
}

watch(page, load);
onMounted(load);
</script>

<template>
    <section>
        <h2 class="mb-4 text-2xl font-semibold">{{ t('admin.nav.audit_logs') }}</h2>
        <p v-if="error" class="mb-3 rounded-lg bg-red-50 p-3 text-red-800" role="alert">{{ errorText(error) }}</p>
        <div class="overflow-x-auto rounded-xl bg-white shadow-sm">
            <table class="w-full min-w-[640px]">
                <thead class="bg-stone-50 text-sm text-stone-600">
                    <tr>
                        <th class="px-3 py-2 text-start">{{ t('admin.fields.created_at') }}</th>
                        <th class="px-3 py-2 text-start">{{ t('admin.audit.action') }}</th>
                        <th class="px-3 py-2 text-start">{{ t('admin.audit.target') }}</th>
                        <th class="px-3 py-2 text-start">{{ t('admin.audit.user') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="row in rows" :key="row.id">
                        <tr class="cursor-pointer border-t hover:bg-emerald-50" @click="open = open === row.id ? null : row.id">
                            <td class="px-3 py-2">{{ dateTime(row.created_at) }}</td>
                            <td class="px-3 py-2 font-mono text-sm">{{ row.action }}</td>
                            <td class="px-3 py-2">{{ row.target_type }} #{{ row.target_id }}</td>
                            <td class="px-3 py-2">#{{ row.user_id }}</td>
                        </tr>
                        <tr v-if="open === row.id" class="bg-stone-50">
                            <td colspan="4" class="px-3 py-2">
                                <div class="grid gap-2 md:grid-cols-2">
                                    <pre class="overflow-x-auto rounded bg-white p-2 text-xs">{{ t('admin.audit.before') }}: {{ JSON.stringify(row.before, null, 2) }}</pre>
                                    <pre class="overflow-x-auto rounded bg-white p-2 text-xs">{{ t('admin.audit.after') }}: {{ JSON.stringify(row.after, null, 2) }}</pre>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <Pager :meta="meta" @page="page = $event" />
    </section>
</template>
