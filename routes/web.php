<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

Route::get('/settings/{operations}', function (Request $request, $operations) {
    return '..' . json_encode(
        collect(explode(',', $operations))->mapWithKeys(function ($item) { return [$item => ['set' => (int)Cache::get($item), 'value' => (int)Cache::get($item)]]; })
    );
});

Route::patch('/settings', function (Request $request) {
    return '..' . json_encode(
        collect($request->json()->all())->map(function ($item, $key) {
            Cache::set($key, $item);
            return ['set' => $item, 'value' => $item];
        })
    );
});