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

$request = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($request)) {
    respond(400, ['error' => 'Request body must be JSON.']);
}

$templateId = (string) ($request['id'] ?? '');
$template = $request['template'] ?? null;
if (!preg_match('/^template_[A-Za-z0-9.]+$/', $templateId)) {
    respond(400, ['error' => 'Invalid template id.']);
}
if (!is_array($template) || empty($template['pages']) || !is_array($template['pages'])) {
    respond(422, ['error' => 'Template must contain a pages array.']);
}

foreach ($template['pages'] as $page) {
    if (!is_array($page) || !isset($page['elements']) || !is_array($page['elements'])) {
        respond(422, ['error' => 'Every page must contain an elements array.']);
    }
    foreach ($page['elements'] as $element) {
        if (!is_array($element) || !in_array($element['type'] ?? '', ['text', 'rectangle', 'ellipse', 'line', 'image'], true)) {
            respond(422, ['error' => 'A template element has an unsupported type.']);
        }
    }
}

$path = __DIR__ . '/uploads/blueprints/' . $templateId . '.json';
if (!is_file($path)) {
    respond(404, ['error' => 'Template not found.']);
}

$existingTemplate = json_decode((string) file_get_contents($path), true);
if (!is_array($existingTemplate)) {
    respond(422, ['error' => 'Template data is invalid.']);
}
if (
    !empty($existingTemplate['dashboard_upload'])
    && (!isset($_SESSION['user_id']) || (int) $_SESSION['user_id'] !== (int) ($existingTemplate['dashboard_owner_id'] ?? 0))
) {
    respond(403, ['error' => 'You are not allowed to edit this template.']);
}

$template['dashboard_upload'] = $existingTemplate['dashboard_upload'] ?? false;
$template['dashboard_owner_id'] = $existingTemplate['dashboard_owner_id'] ?? null;
$template['source'] = $existingTemplate['source'] ?? [];
$template['analysis'] = $existingTemplate['analysis'] ?? [];

$encoded = json_encode(
    $template,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
if ($encoded === false || file_put_contents($path, $encoded, LOCK_EX) === false) {
    respond(500, ['error' => 'Could not write the template JSON.']);
}

respond(200, ['saved' => true]);