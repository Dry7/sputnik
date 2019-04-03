<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

Route::get('/settings/{operations}', function (Request $request, $operations) {
    sleep(5);
    return json_encode(
        collect(explode(',', $operations))->mapWithKeys(function ($item) { return [$item => ['set' => (int)Cache::get($item)+5, 'value' => (int)Cache::get($item)+5]]; })
    );
});

Route::patch('/settings', function (Request $request) {
    sleep(5);
    return json_encode(
        collect($request->json()->all())->map(function ($item, $key) {
            Cache::set($key, $item);
            return ['set' => $item+5, 'value' => $item+5];
        })
    );
});