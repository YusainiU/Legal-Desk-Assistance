<?php
return [
    'scan_enabled' => env('DOCUMENT_SCAN_ENABLED', false),
    'scanner' => env('DOCUMENT_SCAN_BINARY', '/usr/bin/clamscan'),
    'timeout' => (int) env('DOCUMENT_SCAN_TIMEOUT', 60),
];
