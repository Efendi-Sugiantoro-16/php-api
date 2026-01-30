<?php
// test_refresh_endpoints.php
$token = "659f8057f62d9f736dbac775216d470c326c437e41e0b8c96301b8a880f3284724dcb7c09306921e571b7730a35cbbeed99213fbef38c6089e93dc74d2598c64";

function test_url($url, $token) {
    echo "Testing URL: $url\n";
    $options = [
        'http' => [
            'header'  => "Authorization: Bearer $token\r\n",
            'method'  => 'GET',
            'ignore_errors' => true
        ]
    ];
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    $status_line = $http_response_header[0];
    echo "Status: $status_line\n";
    echo "Response: " . substr($result, 0, 500) . "...\n\n";
    return $status_line;
}

test_url("http://localhost:8000/api/goals/index", $token);
test_url("http://localhost:8000/api/dashboard/summary", $token);
?>
