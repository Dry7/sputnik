<?php
declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

Route::get(
    '/settings/{operations}',
    static fn ($operations) => json_encode(
    collect(explode(',', $operations))
            ->mapWithKeys(static fn ($item) => [$item => ['set' => (int)Cache::get($item), 'value' => (int)Cache::get($item)]])
)
);

Route::patch(
    '/settings',
    static fn (Request $request) => json_encode(
    collect($request->json()->all())->map(static function ($item, $key) {
        Cache::set($key, $item);
        return ['set' => $item, 'value' => $item];
    })
)
);
