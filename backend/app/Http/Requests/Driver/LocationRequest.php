<?php

namespace App\Http\Requests\Driver;

use Illuminate\Foundation\Http\FormRequest;

/**
 * One point (`latitude`, `longitude`, `timestamp`, `order_id`) or a batch in `points`
 * (sent by the app after being offline).
 */
class LocationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'points' => ['required_without:latitude', 'array', 'max:500'],
            'points.*.latitude' => ['required', 'numeric', 'between:-90,90'],
            'points.*.longitude' => ['required', 'numeric', 'between:-180,180'],
            'points.*.timestamp' => ['nullable', 'date'],
            'points.*.order_id' => ['nullable', 'integer'],
            'latitude' => ['required_without:points', 'numeric', 'between:-90,90'],
            'longitude' => ['required_without:points', 'numeric', 'between:-180,180'],
            'timestamp' => ['nullable', 'date'],
            'order_id' => ['nullable', 'integer'],
        ];
    }

    /**
     * @return list<array{latitude: float, longitude: float, timestamp: ?string, order_id: ?int}>
     */
    public function points(): array
    {
        $raw = $this->has('points') ? $this->validated('points') : [$this->safe()->only(['latitude', 'longitude', 'timestamp', 'order_id'])];

        return array_map(fn (array $p) => [
            'latitude' => (float) $p['latitude'],
            'longitude' => (float) $p['longitude'],
            'timestamp' => $p['timestamp'] ?? null,
            'order_id' => isset($p['order_id']) ? (int) $p['order_id'] : null,
        ], array_values($raw));
    }
}
