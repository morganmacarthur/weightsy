<?php

declare(strict_types=1);

/**
 * weightsy display-only blog engine
 *
 * - No admin/editor endpoints
 * - SQLite as the only content backend
 * - Markdown body stored in DB and rendered on read
 */

const BLOG_DB_PATH = __DIR__ . '/../blog.sqlite';
const BLOG_SITE_NAME = 'weightsy';
const BLOG_BASE_URL = '/';

$db = openBlogDb();
ensureSchema($db);

$postId = resolveRequestedPostId();

if ($postId === null) {
    renderIndex($db);
    exit;
}

$post = fetchPostById($db, $postId);
if ($post === null || (int) $post['is_published'] !== 1) {
    http_response_code(404);
    renderPage('Post not found', '<p>The requested article was not found.</p>');
    exit;
}

$title = trim((string) $post['title']);
$description = trim((string) $post['description']);
$createdAt = (int) $post['created_at'];
$updatedAt = (int) $post['updated_at'];
$bodyMarkdown = (string) $post['body_md'];
$bodyHtml = markdownToHtml($bodyMarkdown);
$origin = requestOrigin();
$slug = trim((string) $post['slug']);
if ($slug === '') {
    $slug = slugify($title);
}
$canonicalPath = rtrim(BLOG_BASE_URL, '/') . '/' . $postId . '/' . $slug;
$canonicalUrl = $origin . $canonicalPath;

$content = '';
$content .= '<p style="color:#a0a3ad;font-size:13px">';
$content .= 'Published ' . htmlspecialchars(date('Y-m-d', $createdAt), ENT_QUOTES, 'UTF-8');
if ($updatedAt > $createdAt) {
    $content .= ' · Updated ' . htmlspecialchars(date('Y-m-d', $updatedAt), ENT_QUOTES, 'UTF-8');
}
$content .= '</p>';
$content .= '<h1>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1>';
if ($description !== '') {
    $content .= '<p style="font-size:18px;color:#cdd1db"><strong>' . htmlspecialchars($description, ENT_QUOTES, 'UTF-8') . '</strong></p>';
}
$content .= $bodyHtml;
$content .= '<p><a href="' . BLOG_BASE_URL . '">&larr; Back to all posts</a></p>';

$structuredData = [
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => $title,
    'description' => $description,
    'datePublished' => gmdate('c', $createdAt),
    'dateModified' => gmdate('c', $updatedAt > 0 ? $updatedAt : $createdAt),
    'mainEntityOfPage' => [
        '@type' => 'WebPage',
        '@id' => $canonicalUrl,
    ],
    'publisher' => [
        '@type' => 'Organization',
        'name' => BLOG_SITE_NAME,
    ],
];
if ($description === '') {
    unset($structuredData['description']);
}

renderPage(
    $title,
    $content,
    $description,
    canonicalUrl: $canonicalUrl,
    structuredData: $structuredData,
);

function openBlogDb(): SQLite3
{
    $db = new SQLite3(BLOG_DB_PATH);
    $db->exec('PRAGMA journal_mode=WAL;');
    $db->exec('PRAGMA foreign_keys=ON;');
    return $db;
}

function ensureSchema(SQLite3 $db): void
{
    $sql = <<<SQL
CREATE TABLE IF NOT EXISTS posts (
  id INTEGER PRIMARY KEY,
  title TEXT NOT NULL,
  slug TEXT NOT NULL DEFAULT '',
  description TEXT NOT NULL DEFAULT '',
  body_md TEXT NOT NULL,
  created_at INTEGER NOT NULL,
  updated_at INTEGER NOT NULL,
  is_published INTEGER NOT NULL DEFAULT 1
);
CREATE INDEX IF NOT EXISTS idx_posts_published_created ON posts(is_published, created_at DESC);
SQL;
    $db->exec($sql);
}

function resolveRequestedPostId(): ?int
{
    if (isset($_GET['id'])) {
        $id = (int) $_GET['id'];
        return $id > 0 ? $id : null;
    }

    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    if (!is_string($path)) {
        return null;
    }

    // Accept /blog/123 or /blog/123-anything
    if (preg_match('#/blog/(\d+)(?:[-/].*)?$#', $path, $m) === 1) {
        $id = (int) $m[1];
        return $id > 0 ? $id : null;
    }

    return null;
}

function fetchPostById(SQLite3 $db, int $id): ?array
{
    $stmt = $db->prepare('SELECT * FROM posts WHERE id = :id LIMIT 1');
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    if (!$result instanceof SQLite3Result) {
        return null;
    }
    $row = $result->fetchArray(SQLITE3_ASSOC);
    return is_array($row) ? $row : null;
}

function renderIndex(SQLite3 $db): void
{
    $result = $db->query('SELECT id, title, description, created_at FROM posts WHERE is_published = 1 ORDER BY created_at DESC, id DESC');

    $content = '<h1>weightsy blog</h1>';
    $content .= '<p style="color:#a0a3ad">Progress notes and product updates.</p>';

    if (!$result instanceof SQLite3Result) {
        renderPage('Blog', $content . '<p>Unable to load posts.</p>');
        return;
    }

    $rows = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        if (is_array($row)) {
            $rows[] = $row;
        }
    }

    if (count($rows) === 0) {
        $content .= '<p>No posts yet.</p>';
        renderPage('Blog', $content);
        return;
    }

    foreach ($rows as $row) {
        $id = (int) $row['id'];
        $title = htmlspecialchars((string) $row['title'], ENT_QUOTES, 'UTF-8');
        $desc = htmlspecialchars((string) $row['description'], ENT_QUOTES, 'UTF-8');
        $date = htmlspecialchars(date('Y-m-d', (int) $row['created_at']), ENT_QUOTES, 'UTF-8');

        $content .= '<article style="background:#131318;border:1px solid #2a2d36;border-radius:12px;padding:14px;margin:0 0 10px">';
        $content .= '<a href="' . BLOG_BASE_URL . $id . '" style="color:#9feecf;text-decoration:none"><strong>' . $title . '</strong></a>';
        if ($desc !== '') {
            $content .= '<p style="margin:6px 0 0;color:#a0a3ad">' . $desc . '</p>';
        }
        $content .= '<p style="margin:8px 0 0;color:#a0a3ad;font-size:13px">' . $date . '</p>';
        $content .= '</article>';
    }

    renderPage('Blog', $content);
}

function renderPage(
    string $title,
    string $contentHtml,
    string $description = '',
    ?string $canonicalUrl = null,
    ?array $structuredData = null,
): void
{
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $safeDescription = htmlspecialchars($description, ENT_QUOTES, 'UTF-8');

    echo '<!doctype html><html lang="en"><head>';
    echo '<meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . $safeTitle . ' | ' . BLOG_SITE_NAME . '</title>';
    if ($safeDescription !== '') {
        echo '<meta name="description" content="' . $safeDescription . '">';
    }
    if ($canonicalUrl !== null && $canonicalUrl !== '') {
        echo '<link rel="canonical" href="' . htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8') . '">';
    }
    if ($structuredData !== null) {
        $json = json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (is_string($json)) {
            echo '<script type="application/ld+json">' . $json . '</script>';
        }
    }
    echo '<style>';
    echo 'body{margin:0;background:#0b0b0d;color:#eef1f7;font-family:ui-sans-serif,-apple-system,Segoe UI,Roboto,Arial,sans-serif;line-height:1.5;}';
    echo '.wrap{max-width:900px;margin:0 auto;padding:24px;}';
    echo 'a{color:#9feecf;}';
    echo 'pre{background:#131318;border:1px solid #2a2d36;padding:12px;border-radius:10px;overflow:auto;}';
    echo 'code{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;}';
    echo 'blockquote{margin:0;padding:10px 14px;border-left:3px solid #2a2d36;color:#c8ccd6;background:#111319;}';
    echo 'h1,h2,h3{line-height:1.2;}';
    echo '</style>';
    echo '</head><body><main class="wrap">';
    echo $contentHtml;
    echo '</main></body></html>';
}

function requestOrigin(): string
{
    $scheme = 'https';
    if (
        isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
        is_string($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
        $_SERVER['HTTP_X_FORWARDED_PROTO'] !== ''
    ) {
        $scheme = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'])[0]));
    } elseif (isset($_SERVER['REQUEST_SCHEME']) && is_string($_SERVER['REQUEST_SCHEME'])) {
        $scheme = strtolower($_SERVER['REQUEST_SCHEME']);
    } elseif (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' && $_SERVER['HTTPS'] !== '') {
        $scheme = 'https';
    } else {
        $scheme = 'http';
    }

    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host;
}

function slugify(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9\\s-]/', '', $value) ?? $value;
    $value = preg_replace('/\\s+/', '-', $value) ?? $value;
    $value = preg_replace('/-+/', '-', $value) ?? $value;
    return trim($value, '-');
}

function markdownToHtml(string $markdown): string
{
    $text = str_replace(["\r\n", "\r"], "\n", trim($markdown));
    if ($text === '') {
        return '<p></p>';
    }

    $blocks = preg_split('/\n\n+/', $text) ?: [];
    $html = '';

    foreach ($blocks as $block) {
        $trim = trim($block);
        if ($trim === '') {
            continue;
        }

        if (preg_match('/^```([a-zA-Z0-9_-]*)\n([\s\S]*)```$/', $trim, $m) === 1) {
            $code = htmlspecialchars(rtrim($m[2], "\n"), ENT_QUOTES, 'UTF-8');
            $html .= '<pre><code>' . $code . '</code></pre>';
            continue;
        }

        if (preg_match('/^###\s+(.+)$/s', $trim, $m) === 1) {
            $html .= '<h3>' . renderInlineMarkdown($m[1]) . '</h3>';
            continue;
        }
        if (preg_match('/^##\s+(.+)$/s', $trim, $m) === 1) {
            $html .= '<h2>' . renderInlineMarkdown($m[1]) . '</h2>';
            continue;
        }
        if (preg_match('/^#\s+(.+)$/s', $trim, $m) === 1) {
            $html .= '<h1>' . renderInlineMarkdown($m[1]) . '</h1>';
            continue;
        }

        if (preg_match('/^(?:-\s+.+\n?)+$/', $trim) === 1) {
            $lines = preg_split('/\n/', $trim) ?: [];
            $html .= '<ul>';
            foreach ($lines as $line) {
                $line = trim($line);
                if (preg_match('/^-\s+(.+)$/', $line, $m) === 1) {
                    $html .= '<li>' . renderInlineMarkdown($m[1]) . '</li>';
                }
            }
            $html .= '</ul>';
            continue;
        }

        if (preg_match('/^>\s+([\s\S]+)$/', $trim, $m) === 1) {
            $html .= '<blockquote>' . renderInlineMarkdown($m[1]) . '</blockquote>';
            continue;
        }

        $paragraph = preg_replace('/\n+/', ' ', $trim) ?: $trim;
        $html .= '<p>' . renderInlineMarkdown($paragraph) . '</p>';
    }

    return $html;
}

function renderInlineMarkdown(string $text): string
{
    $safe = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

    // links: [text](url)
    $safe = preg_replace_callback('/\[([^\]]+)\]\((https?:\/\/[^\s\)]+)\)/', static function (array $m): string {
        $label = $m[1];
        $url = htmlspecialchars($m[2], ENT_QUOTES, 'UTF-8');
        return '<a href="' . $url . '" target="_blank" rel="noopener noreferrer">' . $label . '</a>';
    }, $safe) ?? $safe;

    // bold **text**
    $safe = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $safe) ?? $safe;

    // italic *text*
    $safe = preg_replace('/\*([^*]+)\*/', '<em>$1</em>', $safe) ?? $safe;

    // inline code `code`
    $safe = preg_replace('/`([^`]+)`/', '<code>$1</code>', $safe) ?? $safe;

    return $safe;
}

