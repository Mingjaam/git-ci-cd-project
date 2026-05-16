<?php
$db_host = getenv('DB_HOST') ?: 'db';
$db_name = getenv('DB_NAME') ?: 'epitec_auth';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '1234';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
    $mysqli->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(503);
    die('데이터베이스 연결 오류. MySQL 서버 상태 및 접속 정보를 확인하세요.');
}
