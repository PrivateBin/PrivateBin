<?php
// Temporary file to force OPcache reset - DELETE AFTER USE
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo json_encode(['status' => 'opcache_reset_done']);
} else {
    echo json_encode(['status' => 'opcache_not_available']);
}
