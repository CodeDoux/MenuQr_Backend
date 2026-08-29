<?php

return [

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    // Ajoute ici tout autre port/domaine utilisé pour servir le frontend
    // (ex. l'IP locale si tu testes depuis un téléphone sur le même Wi-Fi).
    'allowed_origins' => [
        'http://localhost:4200',
        'http://127.0.0.1:4200',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // false : on utilise des tokens Bearer (Sanctum API tokens), pas de
    // cookies de session — pas besoin du mode "stateful" SPA de Sanctum.
    'supports_credentials' => false,

];