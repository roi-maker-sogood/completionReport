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
$templateDatabaseId = is_array($request) ? filter_var($request['id'] ?? null, FILTER_VALIDATE_INT) : false;
if ($templateDatabaseId === false || $templateDatabaseId < 1) {
    respond(400, ['error' => 'A valid template id is required.']);
}

try {
    $pdo = new PDO('mysql:host=localhost;dbname=LLogin;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $select = $pdo->prepare('SELECT id, file_path FROM custom_templates WHERE id = ? AND user_id = ? LIMIT 1');
    $select->execute([$templateDatabaseId, $_SESSION['user_id']]);
    $template = $select->fetch(PDO::FETCH_ASSOC);
    if (!$template) {
        respond(404, ['error' => 'Template not found.']);
    }

    $filePath = (string) $template['file_path'];
    $filesToDelete = [];
    if (preg_match('~^\.\./TemplateCopier/uploads/(template_[A-Za-z0-9.]+)\.pdf$~', $filePath, $matches)) {
        $templateFileId = $matches[1];
        $blueprintPath = __DIR__ . '/../TemplateCopier/uploads/blueprints/' . $templateFileId . '.json';
        $blueprint = is_file($blueprintPath)
            ? json_decode((string) file_get_contents($blueprintPath), true)
            : null;
        if (is_array($blueprint) && (int) ($blueprint['dashboard_owner_id'] ?? 0) !== (int) $_SESSION['user_id']) {
            respond(403, ['error' => 'You are not allowed to delete this template.']);
        }

        $filesToDelete[] = __DIR__ . '/../TemplateCopier/uploads/' . $templateFileId . '.pdf';
        $filesToDelete[] = $blueprintPath;
        for ($page = 1; $page <= 6; $page++) {
            $filesToDelete[] = __DIR__ . '/uploads/templates/' . $templateFileId . '_page_' . $page . '.jpg';
        }
    } elseif (preg_match('~^uploads/templates/([A-Za-z0-9_.-]+\.pdf)$~', $filePath, $matches)) {
        $filesToDelete[] = __DIR__ . '/uploads/templates/' . $matches[1];
    } else {
        respond(422, ['error' => 'This template file path cannot be safely deleted.']);
    }

    foreach ($filesToDelete as $path) {
        if (is_file($path) && !unlink($path)) {
            respond(500, ['error' => 'Could not remove all template files. Check folder permissions and try again.']);
        }
    }

    $delete = $pdo->prepare('DELETE FROM custom_templates WHERE id = ? AND user_id = ?');
    $delete->execute([$templateDatabaseId, $_SESSION['user_id']]);
    if ($delete->rowCount() !== 1) {
        respond(500, ['error' => 'The template files were removed, but the saved template record could not be deleted.']);
    }
} catch (PDOException $exception) {
    error_log('Dashboard template deletion failed: ' . $exception->getMessage());
    respond(500, ['error' => 'Could not delete the saved template. Please try again.']);
}

respond(200, ['deleted' => true]);
