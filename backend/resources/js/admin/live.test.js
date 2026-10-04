import { afterEach, describe, expect, it, vi } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import { ref } from 'vue';
import { makeI18n } from '../shared/i18n';
import { adminMessages } from './messages';
import { boundsPoints, minutesAgo, riderState, sortRiders } from './live/liveMap';

// Leaflet needs real layout; the view only has to hand it the right shapes.
const leaflet = vi.hoisted(() => {
    const calls = { circles: [], lines: [], fit: [] };
    const chain = (extra = {}) => {
        const o = { addTo: () => o, bindTooltip: (el) => Object.assign(o, { tooltip: el }), openTooltip: vi.fn(), ...extra };
        return o;
    };
    const map = chain({ setView: () => map, fitBounds: (p) => calls.fit.push(p), getZoom: () => 12, remove: vi.fn() });
    const L = {
        map: () => map,
        tileLayer: () => chain(),
        layerGroup: () => chain({ clearLayers: () => { calls.circles.length = 0; calls.lines.length = 0; } }),
        circleMarker: (at, opts) => {
            const m = chain({ at, opts });
            calls.circles.push(m);
            return m;
        },
        polyline: (points) => {
            calls.lines.push(points);
            return chain();
        },
    };
    return { L, calls, map };
});
vi.mock('leaflet', () => ({ default: leaflet.L }));
vi.mock('leaflet/dist/leaflet.css', () => ({}));

const { default: LiveMapView } = await import('./views/LiveMapView.vue');

const NOW = new Date('2026-10-04T10:00:00Z').getTime();
const iso = (minAgo) => new Date(NOW - minAgo * 60_000).toISOString();

const LIVE = {
    fresh_minutes: 10,
    stores: [{ id: 1, name: 'Lilongwe Central', latitude: -13.9626, longitude: 33.7741 }],
    waiting_orders: [{ order_id: 9, order_number: 'LLW-0009', ready_at: iso(4), pickup: { latitude: -13.9626, longitude: 33.7741 }, dropoff: { latitude: -13.97, longitude: 33.78 } }],
    drivers: [
        { id: 3, name: 'Zed Idle', vehicle_type: 'BICYCLE', is_online: true, latitude: -13.95, longitude: 33.77, location_updated_at: iso(30), location_fresh: false, delivery: null },
        { id: 2, name: '<b>Banda</b>', vehicle_type: 'MOTORBIKE', is_online: true, latitude: -13.96, longitude: 33.775, location_updated_at: iso(0.2), location_fresh: true, delivery: { order_id: 8, order_number: 'LLW-0008', status: 'ON_THE_WAY', pickup: { latitude: -13.9626, longitude: 33.7741 }, dropoff: { latitude: -13.98, longitude: 33.79 } } },
        { id: 4, name: 'No Fix', vehicle_type: 'CAR', is_online: true, latitude: null, longitude: null, location_updated_at: null, location_fresh: false, delivery: null },
    ],
};

function mountLive(locale = 'en', data = LIVE) {
    const api = { get: vi.fn(async () => ({ data })) };
    const user = { permissions: ['drivers.manage'] };
    const wrapper = mount(LiveMapView, {
        attachTo: document.body,
        global: {
            plugins: [makeI18n(locale, adminMessages)],
            provide: { admin: { api, user: ref(user), languages: ref([]), can: () => true, go: vi.fn(), route: { page: 'live_map', id: null, query: {} } } },
        },
    });
    return { wrapper, api };
}

afterEach(() => vi.useRealTimers());

describe('live map helpers', () => {
    it('classifies riders and sorts busy ones first', () => {
        expect(LIVE.drivers.map(riderState)).toEqual(['stale', 'delivering', 'no_gps']);
        expect(sortRiders(LIVE.drivers).map((d) => d.id)).toEqual([2, 3, 4]);
        expect(minutesAgo(iso(5), NOW)).toBe(5);
        expect(minutesAgo(null, NOW)).toBeNull();
        // Riders without GPS are not part of the map bounds.
        expect(boundsPoints(LIVE)).toHaveLength(2 + 1 + 1);
    });
});

describe('LiveMapView', () => {
    it('plots riders, stores and waiting orders and lists riders with their GPS age', async () => {
        vi.useFakeTimers({ now: NOW, toFake: ['Date', 'setInterval', 'clearInterval'] });
        const { wrapper, api } = mountLive('ja');
        await flushPromises();

        expect(api.get).toHaveBeenCalledWith('/admin/drivers/live');
        // 1 store + 1 waiting order + 2 riders with GPS; one delivery line.
        expect(leaflet.calls.circles).toHaveLength(4);
        expect(leaflet.calls.lines).toEqual([[[-13.96, 33.775], [-13.98, 33.79]]]);
        expect(leaflet.calls.fit.at(-1)).toHaveLength(4);

        // Names are rendered as text, never as HTML.
        const busy = leaflet.calls.circles.find((c) => c.at[0] === -13.96);
        expect(busy.tooltip.querySelector('b')).toBeNull();
        expect(busy.tooltip.textContent).toContain('<b>Banda</b>');

        const rows = wrapper.findAll('[data-test="rider-row"]');
        expect(rows.map((r) => r.find('[data-test="rider-age"]').text())).toEqual(['たった今', '30 分前', 'GPS なし']);
        expect(rows[0].find('[data-test="rider-state"]').text()).toContain('LLW-0008');
        expect(rows[1].find('[data-test="rider-state"]').text()).toBe('10 分以上 GPS なし');
        expect(wrapper.find('[data-test="waiting-row"]').text()).toContain('LLW-0009');

        // Polls on its own.
        vi.advanceTimersByTime(15_000);
        await flushPromises();
        expect(api.get).toHaveBeenCalledTimes(2);
        wrapper.unmount();
    });

    it('says when nobody is online', async () => {
        const { wrapper } = mountLive('en', { ...LIVE, drivers: [], waiting_orders: [] });
        await flushPromises();
        expect(wrapper.text()).toContain('No riders online right now.');
        wrapper.unmount();
    });
});
