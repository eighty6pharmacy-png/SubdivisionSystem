<?php
$url = 'https://www.pagibigfund.gov.ph/images/pagibig_logo.png';
$options = [
    'http' => [
        'method' => 'GET',
        'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n"
    ]
];
$context = stream_context_create($options);
$data = file_get_contents($url, false, $context);
file_put_contents('public/images/pagibig-logo.png', $data);
echo "Logo downloaded successfully!";
