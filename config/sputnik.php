<?php

return [
    'flight_program' => env('FLIGHT_PROGRAM'),
    'exchange_uri' => env('EXCHANGE_URI'),
    'telemetry_freq' => (int)env('TELEMETRY_FREQ', 10),
];
