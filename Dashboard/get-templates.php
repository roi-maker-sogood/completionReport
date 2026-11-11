<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Not authenticated", "data" => []]);
    exit;
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=LLogin;charset=utf8mb4", "root", "");
    $stmt = $pdo->prepare("SELECT id, template_name, file_path, uploaded_at FROM custom_templates WHERE user_id = ? ORDER BY uploaded_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($templates as &$template) {
        $template['cleaned_pages'] = [];
        $filePath = (string) $template['file_path'];
        if (!preg_match('~^\.\./TemplateCopier/uploads/(template_[A-Za-z0-9.]+)\.pdf$~', $filePath, $matches)) {
            continue;
        }

        $templateId = $matches[1];
        $blueprintPath = __DIR__ . '/../TemplateCopier/uploads/blueprints/' . $templateId . '.json';
        $blueprint = is_file($blueprintPath) ? json_decode((string) file_get_contents($blueprintPath), true) : null;
        foreach (array_slice(is_array($blueprint['pages'] ?? null) ? $blueprint['pages'] : [], 0, 6) as $index => $page) {
            $cleanPageName = $templateId . '_page_' . ($index + 1) . '.jpg';
            $cleanPagePath = __DIR__ . '/uploads/templates/' . $cleanPageName;
            if (!is_file($cleanPagePath)) {
                continue;
            }
            $template['cleaned_pages'][] = [
                'file_path' => 'uploads/templates/' . rawurlencode($cleanPageName),
                'width' => (float) ($page['width'] ?? 0),
                'height' => (float) ($page['height'] ?? 0),
                'elements' => is_array($page['elements'] ?? null) ? $page['elements'] : [],
            ];
        }
    }
    unset($template);
    echo json_encode(["status" => "success", "data" => $templates]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>