<?php
return [
    'default' => env('QUEUE_CONNECTION', 'sync'),
    'connections' => ['sync' => ['driver' => 'sync']],
    'failed' => ['driver' => 'database-uuids', 'database' => env('DB_CONNECTION', 'mysql'), 'table' => 'failed_jobs'],
];
