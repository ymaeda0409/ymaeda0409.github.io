<script setup>
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { errorKey } from '../i18n';

const props = defineProps({
    api: { type: Object, required: true },
    deviceName: { type: String, default: 'kitchen-web' },
});
const emit = defineEmits(['logged-in']);

const { t, te } = useI18n();
const email = ref('');
const password = ref('');
const busy = ref(false);
const error = ref(null);

async function submit() {
    busy.value = true;
    error.value = null;
    try {
        const { data } = await props.api.post('/auth/login', {
            email: email.value,
            password: password.value,
            device_name: props.deviceName,
        });
        emit('logged-in', data);
    } catch (e) {
        error.value = e.code;
    } finally {
        busy.value = false;
    }
}
</script>

<template>
    <form class="mx-auto mt-12 max-w-sm space-y-4 rounded-2xl bg-white p-6 shadow" @submit.prevent="submit">
        <h2 class="text-2xl font-semibold">{{ t('login.title') }}</h2>
        <label class="block">
            <span class="mb-1 block">{{ t('login.email') }}</span>
            <input v-model="email" type="email" required autocomplete="username" class="w-full rounded-lg border px-3 py-3 text-lg" />
        </label>
        <label class="block">
            <span class="mb-1 block">{{ t('login.password') }}</span>
            <input v-model="password" type="password" required autocomplete="current-password" class="w-full rounded-lg border px-3 py-3 text-lg" />
        </label>
        <p v-if="error" role="alert" class="text-red-700">{{ t(errorKey(te, error)) }}</p>
        <button type="submit" :disabled="busy" class="w-full rounded-xl bg-emerald-700 py-3 text-lg font-semibold text-white disabled:opacity-50">
            {{ t('login.submit') }}
        </button>
    </form>
</template>
