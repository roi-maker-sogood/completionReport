<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

function respond($status, array $body)
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    respond(401, ['error' => 'Not authenticated.']);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['error' => 'POST is required.']);
}

$request = json_decode((string) file_get_contents('php://input'), true);
$templateId = is_array($request) ? (string) ($request['id'] ?? '') : '';
if (!preg_match('/^template_[A-Za-z0-9.]+$/', $templateId)) {
    respond(400, ['error' => 'Invalid template id.']);
}

$blueprintPath = __DIR__ . '/../TemplateCopier/uploads/blueprints/' . $templateId . '.json';
$blueprint = is_file($blueprintPath) ? json_decode((string) file_get_contents($blueprintPath), true) : null;
if (!is_array($blueprint) || empty($blueprint['dashboard_upload'])) {
    respond(404, ['error' => 'Dashboard template analysis was not found.']);
}
if ((int) ($blueprint['dashboard_owner_id'] ?? 0) !== (int) $_SESSION['user_id']) {
    respond(403, ['error' => 'You are not allowed to complete this upload.']);
}

$sourceFile = basename((string) ($blueprint['source']['saved_file'] ?? ''));
if (!preg_match('/^' . preg_quote($templateId, '/') . '\.pdf$/', $sourceFile)) {
    respond(422, ['error' => 'The uploaded PDF reference is invalid.']);
}
$sourcePath = __DIR__ . '/../TemplateCopier/uploads/' . $sourceFile;
if (!is_file($sourcePath)) {
    respond(404, ['error' => 'The uploaded PDF could not be found.']);
}

$pages = $blueprint['pages'] ?? [];
$pageCount = min(6, count($pages));
if ($pageCount < 1) {
    respond(422, ['error' => 'The analyzed template contains no pages.']);
}
for ($index = 0; $index < $pageCount; $index++) {
    $cleanPage = __DIR__ . '/uploads/templates/' . $templateId . '_page_' . ($index + 1) . '.jpg';
    if (!is_file($cleanPage) || filesize($cleanPage) === 0) {
        respond(422, ['error' => 'Save the cleaned background for every template page before returning to the Dashboard.']);
    }
}

$templateName = trim((string) ($blueprint['source']['template_name'] ?? ''));
if ($templateName === '') {
    $templateName = pathinfo((string) ($blueprint['source']['original_name'] ?? 'Template'), PATHINFO_FILENAME);
}
$filePath = '../TemplateCopier/uploads/' . $sourceFile;

try {
    $pdo = new PDO('mysql:host=localhost;dbname=LLogin;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $find = $pdo->prepare('SELECT id FROM custom_templates WHERE user_id = ? AND file_path = ? LIMIT 1');
    $find->execute([$_SESSION['user_id'], $filePath]);
    $existingId = $find->fetchColumn();

    if ($existingId !== false) {
        $save = $pdo->prepare('UPDATE custom_templates SET template_name = ? WHERE id = ? AND user_id = ?');
        $save->execute([$templateName, $existingId, $_SESSION['user_id']]);
        $databaseId = (int) $existingId;
    } else {
        $save = $pdo->prepare('INSERT INTO custom_templates (user_id, template_name, file_path) VALUES (?, ?, ?)');
        $save->execute([$_SESSION['user_id'], $templateName, $filePath]);
        $databaseId = (int) $pdo->lastInsertId();
    }
} catch (PDOException $exception) {
    error_log('Dashboard template registration failed: ' . $exception->getMessage());
    respond(500, ['error' => 'The template was cleaned but could not be added to your Dashboard.']);
}

respond(200, ['saved' => true, 'template_id' => $databaseId]);
