<script setup>
import { computed, onMounted, provide, ref, shallowRef, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { createApi, getToken, setToken } from '../shared/api';
import { isSupported, rememberLocale } from '../shared/i18n';
import LoginForm from '../shared/components/LoginForm.vue';
import LanguageSwitcher from '../shared/components/LanguageSwitcher.vue';
import { GROUPS, can, createRouter, href, visiblePages } from './navigation';
import DashboardView from './views/DashboardView.vue';
import OrdersView from './views/OrdersView.vue';
import SalesView from './views/SalesView.vue';
import ResourceView from './views/ResourceView.vue';
import StoreProductsView from './views/StoreProductsView.vue';
import CustomersView from './views/CustomersView.vue';
import TranslationsView from './views/TranslationsView.vue';
import SettingsView from './views/SettingsView.vue';
import AuditLogView from './views/AuditLogView.vue';

const props = defineProps({ router: { type: Object, default: null } });

const VIEWS = {
    dashboard: DashboardView,
    orders: OrdersView,
    sales: SalesView,
    store_products: StoreProductsView,
    customers: CustomersView,
    translations: TranslationsView,
    settings: SettingsView,
    audit_logs: AuditLogView,
};

const { locale, t } = useI18n();
const user = ref(null);
const booting = ref(true);
const menuOpen = ref(false);
const languages = shallowRef([]);
const router = props.router ?? createRouter();

const api = createApi({
    locale: () => locale.value,
    onUnauthorized: () => {
        setToken(null);
        user.value = null;
    },
});

const pages = computed(() => visiblePages(user.value));
const current = computed(() => pages.value.find((p) => p.id === router.route.page) ?? pages.value[0] ?? null);
const groups = computed(() => GROUPS
    .map((g) => ({ id: g, pages: pages.value.filter((p) => p.group === g) }))
    .filter((g) => g.pages.length));

provide('admin', {
    api,
    user,
    languages,
    can: (permission) => can(user.value, permission),
    go: router.go,
    route: router.route,
});

function applyUserLanguage(u) {
    // Every staff member works in their own language (users.preferred_language).
    if (u?.preferred_language && isSupported(u.preferred_language)) {
        locale.value = u.preferred_language;
        rememberLocale(u.preferred_language);
    }
}

async function loadLanguages() {
    try {
        languages.value = (await api.get('/languages')).data;
    } catch {
        languages.value = [];
    }
}

async function onLoggedIn({ token, user: u }) {
    setToken(token);
    user.value = u;
    applyUserLanguage(u);
    loadLanguages();
}

async function changeLanguage(code) {
    locale.value = code;
    rememberLocale(code);
    document.documentElement.lang = code;
    if (user.value) {
        try {
            const { data } = await api.put('/account/language', { language: code });
            user.value = data;
        } catch {
            // The UI has switched already; the account copy syncs on the next change.
        }
    }
}

async function logout() {
    try {
        await api.post('/auth/logout');
    } catch {
        // The token may already be invalid.
    }
    setToken(null);
    user.value = null;
}

watch(() => router.route.page, () => {
    menuOpen.value = false;
});

onMounted(async () => {
    document.documentElement.lang = locale.value;
    if (getToken()) {
        try {
            const { data } = await api.get('/auth/me');
            user.value = data;
            loadLanguages();
        } catch {
            setToken(null);
        }
    }
    booting.value = false;
});
</script>

<template>
    <div class="min-h-screen bg-stone-100 text-stone-900">
        <header class="sticky top-0 z-20 flex flex-wrap items-center gap-3 bg-emerald-800 px-4 py-3 text-white">
            <button
                v-if="user"
                class="rounded-lg bg-emerald-700 px-3 py-2 lg:hidden"
                :aria-expanded="menuOpen"
                data-test="menu-toggle"
                @click="menuOpen = !menuOpen"
            >
                ☰ <span class="sr-only">{{ t('admin.menu') }}</span>
            </button>
            <h1 class="text-xl font-semibold">{{ t('admin.title') }}</h1>
            <span v-if="user" class="hidden text-emerald-100 sm:inline">
                {{ user.name }} · {{ t(`admin.enums.role.${user.role}`) }}
            </span>
            <div class="ms-auto flex flex-wrap items-center gap-2">
                <LanguageSwitcher :model-value="locale" @update:model-value="changeLanguage" />
                <button v-if="user" class="rounded-lg bg-emerald-700 px-3 py-2 hover:bg-emerald-600" data-test="logout" @click="logout">
                    {{ t('common.logout') }}
                </button>
            </div>
        </header>

        <template v-if="!booting">
            <LoginForm v-if="!user" :api="api" device-name="admin-web" class="mx-4" @logged-in="onLoggedIn" />

            <div v-else class="lg:flex">
                <nav
                    class="border-e border-stone-200 bg-white lg:sticky lg:top-[60px] lg:block lg:h-[calc(100vh-60px)] lg:w-60 lg:shrink-0 lg:overflow-y-auto"
                    :class="menuOpen ? 'block' : 'hidden'"
                    data-test="nav"
                >
                    <div v-for="group in groups" :key="group.id" class="py-2">
                        <p class="px-4 py-1 text-xs font-semibold uppercase tracking-wide text-stone-500">
                            {{ t(`admin.nav_groups.${group.id}`) }}
                        </p>
                        <a
                            v-for="page in group.pages"
                            :key="page.id"
                            :href="href(page.id)"
                            class="block px-4 py-2 hover:bg-emerald-50"
                            :class="current?.id === page.id ? 'bg-emerald-100 font-semibold text-emerald-900' : ''"
                            :data-test="`nav-${page.id}`"
                        >
                            {{ t(`admin.nav.${page.id}`) }}
                        </a>
                    </div>
                </nav>

                <main class="min-w-0 flex-1 p-4">
                    <p v-if="!current" class="rounded-xl bg-white p-6">{{ t('admin.no_access') }}</p>
                    <ResourceView
                        v-else-if="current.resource"
                        :key="current.id"
                        :name="current.resource"
                        :record-id="router.route.id"
                    />
                    <component :is="VIEWS[current.id]" v-else :key="current.id" :record-id="router.route.id" />
                </main>
            </div>
        </template>
    </div>
</template>
