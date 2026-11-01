<?php
if (session_status() === PHP_SESSION_NONE) session_start();
function e($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function rupiah($value): string { return 'Rp ' . number_format((float)$value, 0, ',', '.'); }
function redirect(string $url): never { header("Location: $url"); exit; }
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function verify_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['_token'] ?? '';
        if (!hash_equals(csrf_token(), $token)) { http_response_code(419); exit('Sesi formulir tidak valid. Muat ulang halaman.'); }
    }
}
function flash(string $key, ?string $message = null): ?string {
    if ($message !== null) { $_SESSION['flash'][$key] = $message; return null; }
    $value = $_SESSION['flash'][$key] ?? null; unset($_SESSION['flash'][$key]); return $value;
}
function post_int(string $key, int $min = 0): int {
    $v = filter_input(INPUT_POST, $key, FILTER_VALIDATE_INT);
    if ($v === false || $v === null || $v < $min) throw new RuntimeException("Nilai $key tidak valid.");
    return $v;
}
function post_money(string $key): float {
    $v = filter_input(INPUT_POST, $key, FILTER_VALIDATE_FLOAT);
    if ($v === false || $v === null || $v < 0) throw new RuntimeException("Nilai $key tidak valid.");
    return (float)$v;
}
function invoice(string $prefix): string { return $prefix . date('YmdHis') . random_int(10,99); }
