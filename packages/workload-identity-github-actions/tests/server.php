<?php

declare(strict_types=1);

// @oagen-ignore-file

// Router for PHP's built-in web server: the mock token source in the tests.
// It records every request in MOCK_DIR/requests.log, one JSON object per
// line, and answers with what MOCK_DIR/response.json describes.

$dir = (string) getenv('MOCK_DIR');

$headers = [];
foreach ($_SERVER as $name => $value) {
    if (str_starts_with($name, 'HTTP_')) {
        $headers[strtolower(str_replace('_', '-', substr($name, 5)))] = $value;
    }
}

file_put_contents(
    $dir . '/requests.log',
    json_encode([
        'method' => $_SERVER['REQUEST_METHOD'],
        'uri' => $_SERVER['REQUEST_URI'],
        'headers' => $headers,
    ]) . "\n",
    FILE_APPEND | LOCK_EX,
);

$response = json_decode((string) file_get_contents($dir . '/response.json'), true);

http_response_code($response['status']);
foreach ($response['headers'] as $name => $value) {
    header($name . ': ' . $value);
}
echo $response['body'];
