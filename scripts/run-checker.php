<?php

while (true) {
    echo "[" . date('Y-m-d H:i:s') . "] Running checker...\n";

    // checker 실행
    passthru("php " . __DIR__ . "/checker.php");

    // 5분마다 실행
    sleep(300); // 300초 = 5분
}