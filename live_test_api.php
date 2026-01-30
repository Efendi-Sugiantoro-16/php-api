<?php
// live_test_api.php
$token = "659f8057f62d9f736dbac775216d470c326c437e41e0b8c96301b8a880f3284724dcb7c09306921e571b7730a35cbbeed99213fbef38c6089e93dc74d2598c64";
$url = "http://localhost:8000/api/goals/store";

$data = [
    'name' => 'Test Goal ' . time(),
    'target_amount' => 1000000,
    'type' => 'digital',
    'description' => 'Test description'
];

$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n" .
                     "Authorization: Bearer $token\r\n",
        'method'  => 'POST',
        'content' => json_encode($data),
        'ignore_errors' => true
    ]
];

$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);
$status_line = $http_response_header[0];

echo "Status: $status_line\n";
echo "Response:\n$result\n";
?>
