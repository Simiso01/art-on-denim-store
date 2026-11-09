<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (api_method() !== 'GET') {
    api_error('Method not allowed.', 405);
}

try {
    $slug = trim((string) ($_GET['slug'] ?? ''));
    if ($slug !== '') {
        $post = find_post_by_slug($slug);
        if (!$post || ($post['status'] ?? '') !== 'published') {
            api_error('Article not found.', 404);
        }
        api_response(true, [
            'title' => $post['title'],
            'slug' => $post['slug'],
            'excerpt' => $post['excerpt'],
            'body' => $post['body'],
            'author_name' => $post['author_name'],
            'published_at' => $post['published_at'],
        ]);
    }

    $posts = published_posts(max(1, min(20, (int) ($_GET['limit'] ?? 12))));
    api_response(true, [
        'items' => array_map(static function ($post) {
            return [
                'title' => $post['title'],
                'slug' => $post['slug'],
                'excerpt' => $post['excerpt'],
                'author_name' => $post['author_name'],
                'published_at' => $post['published_at'],
            ];
        }, $posts),
    ]);
} catch (Throwable $e) {
    error_log('AOD API journal: ' . $e->getMessage());
    api_error('Server error.', 500);
}
