<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/db.php';

use GuzzleHttp\Client;

// 환경 변수 불러오기
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// HTTP 클라이언트
$client = new Client();

// 체크할 모델 (초기 세트)
$checks = [
    [
        'provider' => 'openai',
        'model'    => 'gpt-4o-mini',
        'url'      => 'https://api.openai.com/v1/chat/completions',
        'headers'  => [
            'Authorization' => 'Bearer ' . $_ENV['OPENAI_API_KEY'],
            'Content-Type'  => 'application/json'
        ],
        'body'     => [
            "model" => "gpt-4o-mini",
            "messages" => [["role" => "user", "content" => "ping"]],
            "max_tokens" => 1
        ]
    ],
    // 여기에 Claude, Gemini, Grok, Cohere 등 추가 예정
];

foreach ($checks as $check) {
    $start = microtime(true);
    $ok = false;
    $http_status = null;
    $error_type = null;
    $latency_ms = null;

    try {
        $response = $client->post($check['url'], [
            'headers' => $check['headers'],
            'json'    => $check['body'],
            'timeout' => 3
        ]);

        $http_status = $response->getStatusCode();
        $latency_ms  = round((microtime(true) - $start) * 1000);
        $ok = $http_status === 200;

    } catch (\GuzzleHttp\Exception\ClientException $e) {
        $http_status = $e->getResponse()->getStatusCode();
        $error_type  = "client_error";
        $latency_ms  = round((microtime(true) - $start) * 1000);

    } catch (\GuzzleHttp\Exception\ServerException $e) {
        $http_status = $e->getResponse()->getStatusCode();
        $error_type  = "server_error";
        $latency_ms  = round((microtime(true) - $start) * 1000);

    } catch (\Exception $e) {
        $error_type  = "timeout_or_other";
        $latency_ms  = round((microtime(true) - $start) * 1000);
    }

    // DB 저장
    $stmt = $pdo->prepare("
        INSERT INTO llm_checks
        (ts, provider, model, ok, http_status, error_type, latency_ms)
        VALUES (NOW(), :provider, :model, :ok, :http_status, :error_type, :latency_ms)
    ");

    $stmt->execute([
        ':provider'   => $check['provider'],
        ':model'      => $check['model'],
        ':ok'         => $ok,
        ':http_status'=> $http_status,
        ':error_type' => $error_type,
        ':latency_ms' => $latency_ms
    ]);

    echo "[{$check['provider']}] {$check['model']} → ok={$ok}, http={$http_status}, latency={$latency_ms}ms\n";
}