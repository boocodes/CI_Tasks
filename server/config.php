<?php


return[
    'API_KEY' => getenv('API_KEY') ?: '123',
    'APP_ENV' => getenv('APP_ENV') ?: 'local',
    'DB_PORT' => getenv('DB_PORT') ?: '3306',
    'DB_DATABASE' => getenv('DB_DATABASE') ?: 'task1',
    'DB_USERNAME' => getenv('DB_USERNAME') ?: 'root',
    'DB_PASSWORD' => getenv('DB_PASSWORD') ?: 'root',
];