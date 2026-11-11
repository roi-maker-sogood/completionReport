<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

function respond($status, array $body)
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['error' => 'POST is required.']);
}
if (!isset($_SESSION['user_id'])) {
    respond(401, ['error' => 'Sign in is required.']);
}

$request = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($request)) {
    respond(400, ['error' => 'Request body must be JSON.']);
}

$templateId = (string) ($request['id'] ?? '');
$pageIndex = filter_var($request['page'] ?? null, FILTER_VALIDATE_INT);
$imageData = (string) ($request['image'] ?? '');
if (!preg_match('/^template_[A-Za-z0-9.]+$/', $templateId) || $pageIndex === false || $pageIndex < 0 || $pageIndex >= 6) {
    respond(400, ['error' => 'Invalid template page.']);
}

$blueprintPath = __DIR__ . '/uploads/blueprints/' . $templateId . '.json';
$blueprint = is_file($blueprintPath) ? json_decode((string) file_get_contents($blueprintPath), true) : null;
if (!is_array($blueprint) || empty($blueprint['dashboard_upload'])) {
    respond(404, ['error' => 'Dashboard template not found.']);
}
if ((int) ($blueprint['dashboard_owner_id'] ?? 0) !== (int) $_SESSION['user_id']) {
    respond(403, ['error' => 'You are not allowed to save this template.']);
}
if (!isset($blueprint['pages'][$pageIndex])) {
    respond(400, ['error' => 'The requested page does not exist.']);
}

if (!preg_match('/^data:image\/jpeg;base64,([A-Za-z0-9+\/=]+)$/', $imageData, $matches)) {
    respond(422, ['error' => 'The cleaned page image is invalid.']);
}
$image = base64_decode($matches[1], true);
if ($image === false || $image === '' || strlen($image) > 5 * 1024 * 1024) {
    respond(413, ['error' => 'The cleaned page image is empty or too large.']);
}

$dashboardTemplateDirectory = dirname(__DIR__) . '/Dashboard/uploads/templates/';
if (!is_dir($dashboardTemplateDirectory) && !mkdir($dashboardTemplateDirectory, 0775, true) && !is_dir($dashboardTemplateDirectory)) {
    respond(500, ['error' => 'The Dashboard template folder could not be created.']);
}

$destination = $dashboardTemplateDirectory . $templateId . '_page_' . ($pageIndex + 1) . '.jpg';
if (file_put_contents($destination, $image, LOCK_EX) === false) {
    respond(500, ['error' => 'The cleaned template page could not be saved.']);
}

respond(200, ['saved' => true]);
