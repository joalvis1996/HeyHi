<?php

while (true) {
    echo "[" . date('Y-m-d H:i:s') . "] Running checker...\n";

    // checker 실행
    passthru("php " . __DIR__ . "/checker.php");

    // 5분마다 실행
    sleep(300); // 300초 = 5분
}


// <?php
// // scripts/run-checker-once.php
// // 크론용: checker.php를 한 번 실행하고 종료

// declare(strict_types=1);

// $checkerPath = realpath(__DIR__ . '/../checker.php');

// if (!$checkerPath || !file_exists($checkerPath)) {
//     fwrite(STDERR, "checker.php not found at: {$checkerPath}\n");
//     exit(1);
// }

// $ts = date('Y-m-d H:i:s');
// echo "[{$ts}] Running checker once...\n";

// $cmd = escapeshellcmd(PHP_BINARY) . ' ' . escapeshellarg($checkerPath);
// passthru($cmd, $exitCode);

// echo "[{$ts}] done (exit={$exitCode})\n";
// exit($exitCode);
