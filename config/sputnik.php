<?php

return [
    'flight_program' => (string)env('FLIGHT_PROGRAM'),
    'exchange_uri' => (string)env('EXCHANGE_URI'),
    'exchange_timeout' => 0.1,
    'telemetry_freq' => (int)env('TELEMETRY_FREQ', 10),
    'terminate' => env('TERMINATE', true),
];
