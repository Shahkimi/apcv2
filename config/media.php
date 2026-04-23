<?php

$seconds = (int) env('MEDIA_PAPARAN_REFRESH_SECONDS', 30);
$seconds = max(1, $seconds);

return [
    'paparan_refresh_seconds' => $seconds,
    'paparan_refresh_ms' => $seconds * 1000,
];
