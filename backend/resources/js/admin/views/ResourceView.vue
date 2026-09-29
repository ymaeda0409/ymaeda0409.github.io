<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useAdmin, useRelationOptions, clearRelationCache } from '../useAdmin';
import { RESOURCES } from '../resources';
import { toPayload } from '../resources';
import ResourceForm from '../components/ResourceForm.vue';
import Pager from '../components/Pager.vue';

/**
 * List + create/edit screen for one entry of RESOURCES.
 * Route: #/<name> (list), #/<name>/new, #/<name>/<id>.
 */
const props = defineProps({
    name: { type: String, required: true },
    recordId: { type: String, default: null },
});

const { api, t, can, go, money, enumText, text, errorText } = useAdmin();
const { load: loadOptions, label: relationLabel } = useRelationOptions(api);
const resource = computed(() => RESOURCES[props.name]);
const canManage = computed(() => can(resource.value.manage));
const canCreate = computed(() => can(resource.value.create ?? resource.value.manage));

const rows = ref([]);
const meta = ref(null);
const page = ref(1);
const loading = ref(false);
const listError = ref(null);
const relationLabels = reactive({});

const form = ref(null);
const creating = computed(() => props.recordId === 'new');
const saving = ref(false);
const formError = ref(null);
const fieldErrors = ref({});
const saved = ref(false);

const fieldByName = computed(() => Object.fromEntries(resource.value.fields.map((f) => [f.name, f])));

async function loadList() {
    loading.value = true;
    listError.value = null;
    try {
        const res = await api.get(resource.value.endpoint, { page: page.value, per_page: 20 });
        rows.value = res.data;
        meta.value = res.meta?.pagination ?? null;
    } catch (e) {
        listError.value = e;
    } finally {
        loading.value = false;
    }
}

async function loadRelationLabels() {
    for (const column of resource.value.columns) {
        const field = fieldByName.value[column];
        if (field?.type === 'relation' && !relationLabels[field.source]) {
            try {
                const options = await loadOptions(field.source);
                // Rows are kept (not labels) so names follow later language switches.
                relationLabels[field.source] = Object.fromEntries(options.map((o) => [o.value, o.row]));
            } catch {
                relationLabels[field.source] = {};
            }
        }
    }
}

// After "create" the route moves to the new record; keep the confirmation visible there.
let justCreated = false;

async function loadRecord() {
    formError.value = null;
    fieldErrors.value = {};
    saved.value = justCreated;
    justCreated = false;
    if (!props.recordId) {
        form.value = null;
        return;
    }
    if (creating.value) {
        form.value = JSON.parse(JSON.stringify(resource.value.defaults ?? {}));
        return;
    }
    try {
        const { data } = await api.get(`${resource.value.endpoint}/${props.recordId}`);
        form.value = data;
    } catch (e) {
        form.value = null;
        formError.value = e;
    }
}

async function save() {
    saving.value = true;
    formError.value = null;
    fieldErrors.value = {};
    saved.value = false;
    try {
        const payload = toPayload(resource.value, form.value, { creating: creating.value });
        const { data } = creating.value
            ? await api.post(resource.value.endpoint, payload)
            : await api.put(`${resource.value.endpoint}/${props.recordId}`, payload);
        clearRelationCache(props.name);
        saved.value = true;
        if (creating.value) {
            justCreated = true;
            go(props.name, data.id);
        } else {
            form.value = data;
        }
    } catch (e) {
        formError.value = e;
        fieldErrors.value = e.fields ?? {};
    } finally {
        saving.value = false;
    }
}

async function remove() {
    if (!window.confirm(t('admin.confirm_delete'))) return;
    try {
        await api.delete(`${resource.value.endpoint}/${props.recordId}`);
        clearRelationCache(props.name);
        go(props.name);
    } catch (e) {
        formError.value = e;
    }
}

function cell(row, column) {
    const value = row[column];
    const field = fieldByName.value[column];
    if (typeof value === 'boolean') return value ? '✓' : '—';
    if (field?.type === 'translations') return text(value);
    if (field?.type === 'money') return money(value);
    if (field?.type === 'relation') {
        const related = relationLabels[field.source]?.[value];
        return related ? relationLabel(related) : (value ?? '');
    }
    if (field?.enum) return enumText(field.enum, value);
    return value ?? '';
}

const title = computed(() => {
    if (creating.value) return t('admin.new_record', { name: t(`admin.nav.${props.name}`) });
    if (!form.value) return t(`admin.nav.${props.name}`);
    const titleField = resource.value.titleField;
    return titleField === 'translations' ? text(form.value.translations) : (form.value.name ?? form.value.code ?? `#${form.value.id}`);
});

watch(() => props.recordId, (id) => {
    loadRecord();
    if (!id) loadList();
});
watch(page, loadList);

onMounted(() => {
    loadRelationLabels();
    loadRecord();
    if (!props.recordId) loadList();
});
</script>

<template>
    <section>
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <a v-if="recordId" :href="`#/${name}`" class="rounded-lg bg-white px-3 py-2" data-test="back">← {{ t('admin.back') }}</a>
            <h2 class="text-2xl font-semibold">{{ recordId ? title : t(`admin.nav.${name}`) }}</h2>
            <a
                v-if="!recordId && canCreate"
                :href="`#/${name}/new`"
                class="ms-auto rounded-lg bg-emerald-700 px-4 py-2 font-semibold text-white"
                data-test="create"
            >
                + {{ t('admin.create') }}
            </a>
        </div>

        <!-- List -->
        <template v-if="!recordId">
            <p v-if="listError" class="rounded-lg bg-red-50 p-3 text-red-800" role="alert">{{ errorText(listError) }}</p>
            <div class="overflow-x-auto rounded-xl bg-white shadow-sm">
                <table class="w-full min-w-[640px] text-start">
                    <thead class="bg-stone-50 text-sm text-stone-600">
                        <tr>
                            <th v-for="column in resource.columns" :key="column" class="px-3 py-2 text-start">{{ t(`admin.fields.${column}`) }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="!loading && !rows.length">
                            <td :colspan="resource.columns.length" class="px-3 py-6 text-center text-stone-500">{{ t('admin.empty') }}</td>
                        </tr>
                        <tr
                            v-for="row in rows"
                            :key="row.id"
                            class="cursor-pointer border-t hover:bg-emerald-50"
                            data-test="row"
                            @click="go(name, row.id)"
                        >
                            <td v-for="column in resource.columns" :key="column" class="px-3 py-2">{{ cell(row, column) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pager :meta="meta" @page="page = $event" />
        </template>

        <!-- Form -->
        <form v-else-if="form" class="rounded-xl bg-white p-4 shadow-sm" @submit.prevent="save">
            <ResourceForm v-model="form" :resource="resource" :creating="creating" :errors="fieldErrors" :readonly="!canManage" />
            <p v-if="formError" class="mt-4 rounded-lg bg-red-50 p-3 text-red-800" role="alert" data-test="form-error">{{ errorText(formError) }}</p>
            <p v-if="saved" class="mt-4 rounded-lg bg-emerald-50 p-3 text-emerald-900" role="status">{{ t('admin.saved') }}</p>
            <div v-if="canManage || (creating && canCreate)" class="mt-4 flex flex-wrap gap-2">
                <button type="submit" class="rounded-lg bg-emerald-700 px-5 py-2 font-semibold text-white disabled:opacity-50" :disabled="saving" data-test="save">
                    {{ t('admin.save') }}
                </button>
                <button
                    v-if="!creating && !resource.noDelete"
                    type="button"
                    class="ms-auto rounded-lg bg-red-50 px-4 py-2 text-red-800"
                    data-test="delete"
                    @click="remove"
                >
                    {{ t('admin.delete') }}
                </button>
            </div>
        </form>
        <p v-else-if="formError" class="rounded-lg bg-red-50 p-3 text-red-800" role="alert">{{ errorText(formError) }}</p>
    </section>
</template>
