<?php

return [
    'email' => env('ADMIN_EMAIL', 'admin'),
    'password' => env('ADMIN_PASSWORD_HASH'),
    /*
     * Reservations currently store the vehicle name rather than a vehicle
     * record. Keep this list in one place until a vehicle domain is introduced.
     */
    'vehicles' => [
        'Toyota Vios',
        'Mitsubishi Mirage',
        'Honda Civic',
        'Toyota Fortuner',
    ],
];
