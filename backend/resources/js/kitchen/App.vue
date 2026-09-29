<script setup>
import { onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { createApi, getToken, setToken } from '../shared/api';
import { isSupported, rememberLocale } from '../shared/i18n';
import LoginForm from './components/LoginForm.vue';
import KitchenBoard from './components/KitchenBoard.vue';
import LanguageSwitcher from './components/LanguageSwitcher.vue';

const { locale, t } = useI18n();
const user = ref(null);
const booting = ref(true);

const api = createApi({
    locale: () => locale.value,
    onUnauthorized: () => {
        setToken(null);
        user.value = null;
    },
});

function applyUserLanguage(u) {
    // Staff members each have their own language (users.preferred_language).
    if (u?.preferred_language && isSupported(u.preferred_language)) {
        locale.value = u.preferred_language;
        rememberLocale(u.preferred_language);
    }
}

async function onLoggedIn({ token, user: u }) {
    setToken(token);
    user.value = u;
    applyUserLanguage(u);
}

async function changeLanguage(code) {
    locale.value = code;
    rememberLocale(code);
    if (user.value) {
        try {
            const { data } = await api.put('/account/language', { language: code });
            user.value = data;
        } catch {
            // UI already switched; the server copy syncs on the next change.
        }
    }
}

async function logout() {
    try {
        await api.post('/auth/logout');
    } catch {
        // Token may already be invalid.
    }
    setToken(null);
    user.value = null;
}

onMounted(async () => {
    if (getToken()) {
        try {
            const { data } = await api.get('/auth/me');
            user.value = data;
        } catch {
            setToken(null);
        }
    }
    booting.value = false;
});
</script>

<template>
    <div class="min-h-screen bg-stone-100 text-stone-900">
        <header class="flex flex-wrap items-center gap-3 bg-emerald-800 px-4 py-3 text-white">
            <h1 class="text-xl font-semibold">{{ t('kitchen.title') }}</h1>
            <span v-if="user" class="text-emerald-100">{{ user.name }}</span>
            <div class="ms-auto flex flex-wrap items-center gap-2">
                <LanguageSwitcher :model-value="locale" @update:model-value="changeLanguage" />
                <button v-if="user" class="rounded-lg bg-emerald-700 px-3 py-2 hover:bg-emerald-600" @click="logout">
                    {{ t('common.logout') }}
                </button>
            </div>
        </header>

        <main class="p-4">
            <template v-if="!booting">
                <KitchenBoard v-if="user" :api="api" />
                <LoginForm v-else :api="api" @logged-in="onLoggedIn" />
            </template>
        </main>
    </div>
</template>
