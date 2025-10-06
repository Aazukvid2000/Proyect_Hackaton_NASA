<?php
header('Content-Type: application/json');

define('CLAUDE_API_KEY', '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');

function llamarClaude($prompt) {
    $apiKey = CLAUDE_API_KEY;
    
    $data = [
        'model' => 'claude-3-haiku-20240307',
        'max_tokens' => 1024,
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ]
    ];
    
    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'x-api-key: ' . $apiKey,
        'anthropic-version: 2023-06-01'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'http_code' => $httpCode,
        'response' => json_decode($response, true)
    ];
}

$resultado = llamarClaude('Di "funciona" si puedes leerme');
echo json_encode($resultado, JSON_PRETTY_PRINT);
?>