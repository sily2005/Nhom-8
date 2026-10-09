<?php

$caPath = getenv('MYSQL_ATTR_SSL_CA');
if (!$caPath || !file_exists($caPath)) {
    echo "CA certificate file not found at: {$caPath}\n";
    exit(1);
}

$content = file_get_contents($caPath);
if (!str_contains($content, '-----BEGIN CERTIFICATE-----')) {
    echo "Invalid CA certificate format.\n";
    exit(1);
}

echo "CA certificate is valid and readable.\n";
exit(0);
