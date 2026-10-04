/**
 * Pure helpers for the rider live map, kept out of the component so they can be
 * tested without a map library.
 */

/** Whole minutes since an ISO timestamp (null when there is no fix yet). */
export function minutesAgo(iso, now = Date.now()) {
    if (!iso) return null;
    return Math.max(0, Math.floor((now - new Date(iso).getTime()) / 60_000));
}

/** 'delivering' | 'free' | 'stale' | 'no_gps' — drives marker colour and the list badge. */
export function riderState(rider) {
    if (rider.latitude === null || rider.longitude === null) return 'no_gps';
    if (!rider.location_fresh) return 'stale';
    return rider.delivery ? 'delivering' : 'free';
}

export const STATE_COLORS = {
    delivering: '#047857', // emerald-700
    free: '#2563eb', // blue-600
    stale: '#a8a29e', // stone-400
    no_gps: '#a8a29e',
};

/** Every point the map should fit: riders with GPS, stores and waiting orders. */
export function boundsPoints(live) {
    if (!live) return [];
    return [
        ...live.drivers.filter((d) => d.latitude !== null && d.longitude !== null).map((d) => [d.latitude, d.longitude]),
        ...live.stores.map((s) => [s.latitude, s.longitude]),
        ...live.waiting_orders.map((o) => [o.dropoff.latitude, o.dropoff.longitude]),
    ];
}

/** Riders on a delivery first, then free, then stale / no GPS; by name within a group. */
export function sortRiders(drivers) {
    const order = { delivering: 0, free: 1, stale: 2, no_gps: 3 };
    return [...drivers].sort((a, b) => order[riderState(a)] - order[riderState(b)] || String(a.name).localeCompare(String(b.name)));
}
