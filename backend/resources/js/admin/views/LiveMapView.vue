<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { useAdmin, useLoader } from '../useAdmin';
import { statusKey } from '../../shared/i18n';
import { STATE_COLORS, boundsPoints, minutesAgo, riderState, sortRiders } from '../live/liveMap';

const props = defineProps({
    refreshMs: { type: Number, default: 15_000 },
    tileUrl: { type: String, default: import.meta.env.VITE_MAP_TILE_URL || 'https://tile.openstreetmap.org/{z}/{x}/{y}.png' },
});

const { api, t, te, enumText, number, errorText } = useAdmin();
const { state, run } = useLoader(async () => (await api.get('/admin/drivers/live')).data);

const live = computed(() => state.data);
const riders = computed(() => sortRiders(live.value?.drivers ?? []));
const now = ref(Date.now());
const mapEl = ref(null);

let map = null;
let layer = null;
let fitted = false;
const riderMarkers = new Map();

function ago(iso) {
    const m = minutesAgo(iso, now.value);
    if (m === null) return t('admin.live.no_gps');
    return m < 1 ? t('admin.live.just_now') : t('admin.live.minutes_ago', { n: number(m) });
}

function stateText(rider) {
    const s = riderState(rider);
    if (s === 'stale') return t('admin.live.state_stale', { n: number(live.value.fresh_minutes) });
    return t(`admin.live.state_${s}`);
}

// Names come from user data, so tooltips are built with textContent (never HTML).
function label(...lines) {
    const el = document.createElement('div');
    lines.filter(Boolean).forEach((line, i) => {
        const row = document.createElement(i === 0 ? 'strong' : 'div');
        row.textContent = line;
        el.appendChild(row);
    });
    return el;
}

function draw() {
    if (!map || !live.value) return;
    layer.clearLayers();
    riderMarkers.clear();

    for (const store of live.value.stores) {
        L.circleMarker([store.latitude, store.longitude], { radius: 7, color: '#78350f', fillColor: '#f59e0b', fillOpacity: 1, weight: 2 })
            .bindTooltip(label(store.name, t('admin.live.store')))
            .addTo(layer);
    }
    for (const order of live.value.waiting_orders) {
        L.circleMarker([order.dropoff.latitude, order.dropoff.longitude], { radius: 6, color: '#b91c1c', fillColor: '#fecaca', fillOpacity: 1, weight: 2, dashArray: '3' })
            .bindTooltip(label(order.order_number, t('admin.live.waiting_order')))
            .addTo(layer);
    }
    for (const rider of live.value.drivers) {
        if (rider.latitude === null || rider.longitude === null) continue;
        const color = STATE_COLORS[riderState(rider)];
        const at = [rider.latitude, rider.longitude];
        if (rider.delivery) {
            L.polyline([at, [rider.delivery.dropoff.latitude, rider.delivery.dropoff.longitude]], { color, weight: 3, dashArray: '6 6' }).addTo(layer);
        }
        const marker = L.circleMarker(at, { radius: 10, color: '#ffffff', fillColor: color, fillOpacity: 1, weight: 3 })
            .bindTooltip(label(rider.name, enumText('vehicle_type', rider.vehicle_type), stateText(rider), ago(rider.location_updated_at)))
            .addTo(layer);
        riderMarkers.set(rider.id, marker);
    }

    if (!fitted) fitAll();
}

function fitAll() {
    const points = boundsPoints(live.value);
    if (!map || !points.length) return;
    map.fitBounds(points, { padding: [32, 32], maxZoom: 15 });
    fitted = true;
}

function focus(rider) {
    const marker = riderMarkers.get(rider.id);
    if (!map || !marker) return;
    map.setView([rider.latitude, rider.longitude], Math.max(map.getZoom(), 15));
    marker.openTooltip();
}

async function refresh() {
    now.value = Date.now();
    await run();
}

watch(live, draw);

let timer = null;
onMounted(() => {
    map = L.map(mapEl.value, { zoomControl: true }).setView([-13.9626, 33.7741], 12); // Lilongwe until data arrives
    L.tileLayer(props.tileUrl, { maxZoom: 19, attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
    layer = L.layerGroup().addTo(map);
    refresh();
    timer = setInterval(refresh, props.refreshMs);
});
onUnmounted(() => {
    clearInterval(timer);
    map?.remove();
    map = null;
});
</script>

<template>
    <section>
        <div class="mb-4 flex flex-wrap items-center gap-2">
            <h2 class="text-2xl font-semibold">{{ t('admin.nav.live_map') }}</h2>
            <span class="text-sm text-stone-500">{{ t('admin.live.auto_refresh', { n: number(refreshMs / 1000) }) }}</span>
            <button class="ms-auto rounded-lg bg-white px-3 py-2 shadow-sm hover:bg-stone-50" data-test="fit-all" @click="fitAll">
                {{ t('admin.live.fit_all') }}
            </button>
        </div>
        <p v-if="state.error" class="mb-3 rounded-lg bg-red-50 p-3 text-red-800" role="alert">{{ errorText(state.error) }}</p>

        <div class="grid gap-4 xl:grid-cols-[1fr_22rem]">
            <div
                ref="mapEl"
                class="z-0 h-[60vh] min-h-80 rounded-xl bg-stone-200 shadow-sm"
                role="img"
                :aria-label="t('admin.live.map_label')"
                data-test="map"
            />

            <div class="grid content-start gap-4">
                <ul class="flex flex-wrap gap-x-4 gap-y-1 text-sm" data-test="legend">
                    <li v-for="s in ['delivering', 'free', 'stale']" :key="s" class="flex items-center gap-1">
                        <span class="inline-block h-3 w-3 rounded-full" :style="{ background: STATE_COLORS[s] }" />
                        {{ s === 'stale' ? t('admin.live.state_stale', { n: number(live?.fresh_minutes ?? 10) }) : t(`admin.live.state_${s}`) }}
                    </li>
                    <li class="flex items-center gap-1"><span class="inline-block h-3 w-3 rounded-full bg-amber-500" />{{ t('admin.live.store') }}</li>
                    <li class="flex items-center gap-1"><span class="inline-block h-3 w-3 rounded-full border-2 border-red-700 bg-red-200" />{{ t('admin.live.waiting_order') }}</li>
                </ul>

                <div class="rounded-xl bg-white p-4 shadow-sm">
                    <h3 class="mb-2 font-semibold">{{ t('admin.live.riders', { n: number(riders.length) }) }}</h3>
                    <p v-if="live && !riders.length" class="text-stone-500">{{ t('admin.live.no_riders') }}</p>
                    <ul class="divide-y">
                        <li v-for="rider in riders" :key="rider.id" data-test="rider-row">
                            <button class="w-full py-2 text-start hover:bg-stone-50 disabled:cursor-default" :disabled="rider.latitude === null" @click="focus(rider)">
                                <span class="flex items-center gap-2">
                                    <span class="inline-block h-3 w-3 shrink-0 rounded-full" :style="{ background: STATE_COLORS[riderState(rider)] }" />
                                    <span class="font-medium">{{ rider.name }}</span>
                                    <span class="text-sm text-stone-500">{{ enumText('vehicle_type', rider.vehicle_type) }}</span>
                                    <span class="ms-auto text-sm text-stone-500" data-test="rider-age">{{ ago(rider.location_updated_at) }}</span>
                                </span>
                                <span class="block ps-5 text-sm" :class="riderState(rider) === 'stale' ? 'text-amber-700' : 'text-stone-600'" data-test="rider-state">
                                    <template v-if="rider.delivery">{{ rider.delivery.order_number }} · {{ t(statusKey(te, rider.delivery.status)) }}</template>
                                    <template v-else>{{ stateText(rider) }}</template>
                                </span>
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="rounded-xl bg-white p-4 shadow-sm">
                    <h3 class="mb-2 font-semibold">{{ t('admin.live.waiting_orders', { n: number(live?.waiting_orders.length ?? 0) }) }}</h3>
                    <p v-if="live && !live.waiting_orders.length" class="text-stone-500">{{ t('admin.empty') }}</p>
                    <ul class="divide-y">
                        <li v-for="order in live?.waiting_orders ?? []" :key="order.order_id" class="flex py-2" data-test="waiting-row">
                            <a :href="`#/orders/${order.order_id}`" class="hover:underline">{{ order.order_number }}</a>
                            <span class="ms-auto text-sm text-stone-500">{{ ago(order.ready_at) }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
</template>
