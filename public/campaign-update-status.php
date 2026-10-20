<?php

// Standalone progress survives maintenance mode and replacement of vendor/app files.
declare(strict_types=1);
header('Content-Type: application/json');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
$id = $_GET['job'] ?? '';
if (! is_string($id) || ! preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/D', $id)) {
    http_response_code(404);
    exit;
}
$path = dirname(__DIR__).'/storage/app/updates/jobs/'.$id.'/journal.json';
$job = is_file($path) ? json_decode(file_get_contents($path), true) : null;
$token = $_COOKIE['cm_update_'.$id] ?? '';
if (! $job || ! is_string($token) || strlen($token) !== 64 || ! hash_equals($job['status_token_hash'] ?? '', hash('sha256', $token))) {
    http_response_code(404);
    exit;
}
// Only the initiating, authenticated capability cookie reaches this point.
// The supervisor checks process leases before recovering an interrupted job.
$supervisor = dirname($path).'/engine/UpdateSupervisor.php';
if (is_file($supervisor)) {
    require_once $supervisor;
    \App\Services\Updates\UpdateSupervisor::kick(dirname($path));
}
echo json_encode(array_intersect_key($job, array_flip(['state', 'message', 'source_version', 'target_version', 'updated_at', 'portal_receipt_pending', 'worker_notice'])), JSON_THROW_ON_ERROR);
