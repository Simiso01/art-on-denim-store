<?php
/**
 * API â€” Notifications (authenticated)
 *
 * GET                      list
 * POST ?action=read        mark one {id}
 * POST ?action=read_all    mark all
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$user = api_require_user();
$userId = (int) $user['id'];
$method = api_method();
$action = $_GET['action'] ?? '';
$body = api_json_body();

try {
    if ($method === 'GET') {
        $items = get_user_notifications($userId);
        api_response(true, [
            'unread_count' => unread_notification_count($userId),
            'items' => array_map(static function ($n) {
                return [
                    'id' => (int) $n['id'],
                    'type' => $n['type'],
                    'title' => $n['title'],
                    'message' => $n['message'],
                    'link' => $n['link'],
                    'is_read' => (bool) $n['is_read'],
                    'created_at' => $n['created_at'],
                ];
            }, $items),
        ]);
    }

    if ($method === 'POST' && $action === 'read_all') {
        mark_all_notifications_read($userId);
        api_response(true, ['unread_count' => 0], 'All notifications marked as read.');
    }

    if ($method === 'POST' && ($action === 'read' || isset($body['id']))) {
        $id = (int) ($body['id'] ?? 0);
        if ($id < 1) {
            api_error('Notification id is required.', 422);
        }
        mark_notification_read($userId, $id);
        api_response(true, [
            'unread_count' => unread_notification_count($userId),
        ], 'Notification marked as read.');
    }

    api_error('Unknown action or method.', 405);
} catch (Throwable $e) {
    error_log('AOD API notifications: ' . $e->getMessage());
    api_error('Server error.', 500);
}
