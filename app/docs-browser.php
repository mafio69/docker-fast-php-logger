<?php
declare(strict_types=1);

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {

$docsRoot = '/var/www/html/project';
$scanDirs = ['docs', '.'];
$exclude = ['vendor', '.kiro', 'node_modules', '.git'];

// Collect all .md files
$files = [];
if (is_dir($docsRoot)) {
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($docsRoot)) as $file) {
        if ($file->getExtension() !== 'md') continue;
        $rel = str_replace($docsRoot . '/', '', $file->getPathname());
        foreach ($exclude as $ex) {
            if (str_starts_with($rel, $ex . '/')) continue 2;
        }
        $files[] = $rel;
    }
}
sort($files);

// If file requested, render it
$requested = $_GET['file'] ?? null;
if ($requested && in_array($requested, $files, true)) {
    $content = file_get_contents($docsRoot . '/' . $requested);
    if ($content === false) {
        throw new RuntimeException('Failed to read file: ' . $requested);
    }
    $html = '<pre style="white-space:pre-wrap;word-wrap:break-word;max-width:900px;margin:0 auto;padding:40px;font-family:monospace;color:#c9d1d9">' . htmlspecialchars($content) . '</pre>';
    // Try to use GitHub API for rendering if available
    $token = getenv('GITHUB_TOKEN') ?: getenv('GIT_ACCES_TOKEN');
    if ($token) {
        $ch = curl_init('https://api.github.com/markdown');
        if ($ch === false) {
            throw new RuntimeException('Failed to initialize cURL');
        }
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: token ' . $token,
                'Content-Type: application/json',
                'User-Agent: md-viewer',
            ],
            CURLOPT_POSTFIELDS => json_encode(['text' => $content, 'mode' => 'gfm']),
        ]);
        $result = curl_exec($ch);
        $curlError = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($result === false) {
            error_log('GitHub Markdown API cURL error: ' . $curlError);
        } elseif ($code === 200) {
            // Strip dangerous tags from the API response to prevent XSS
            $allowedTags = '<h1><h2><h3><h4><h5><h6><p><a><ul><ol><li><br><em><strong>'
                . '<code><pre><blockquote><table><thead><tbody><tr><th><td><img><hr><div><span><del><sup><sub><dl><dt><dd>';
            $sanitized = strip_tags($result, $allowedTags);
            $html = '<div style="max-width:900px;margin:0 auto;padding:40px" class="markdown-body">' . $sanitized . '</div>';
        } else {
            error_log('GitHub Markdown API returned HTTP ' . $code);
        }
    }
    ?><!DOCTYPE html><html><head><meta charset="utf-8"><title><?= htmlspecialchars($requested) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/github-markdown-css/5.5.1/github-markdown-dark.min.css">
    <style>body{background:#0d1117;color:#c9d1d9;margin:0}.back{position:fixed;top:10px;left:10px;color:#58a6ff;font-family:monospace;text-decoration:none}.back:hover{text-decoration:underline}</style>
    </head><body><a class="back" href="docs-browser.php">← Wróć</a><?= $html ?></body></html><?php
    exit;
}

} catch (Throwable $e) { ?>
<!DOCTYPE html><html><head><meta charset="utf-8"><title>Error</title>
<style>body{background:#0d1117;color:#f85149;font-family:monospace;padding:40px;max-width:700px;margin:0 auto}pre{background:#161b22;border:1px solid #30363d;padding:16px;border-radius:4px;color:#c9d1d9;overflow-x:auto;margin-top:12px}</style>
</head><body>
<h2><?= htmlspecialchars($e->getMessage()) ?></h2>
<p style="color:#8b949e"><?= htmlspecialchars(basename($e->getFile())) ?>:<?= $e->getLine() ?></p>
<pre><?= htmlspecialchars($e->getTraceAsString()) ?></pre>
</body></html>
<?php exit; } ?>
<!DOCTYPE html><html><head><meta charset="utf-8"><title>📖 Docs Browser</title>
<style>
body{background:#0d1117;color:#c9d1d9;font-family:monospace;padding:40px;max-width:700px;margin:0 auto}
a{color:#58a6ff;text-decoration:none}a:hover{text-decoration:underline}
h1{color:#4ec9b0;font-size:1.4em}
li{padding:6px 0;border-bottom:1px solid #21262d}
ul{list-style:none;padding:0}
input{background:#161b22;border:1px solid #30363d;color:#c9d1d9;padding:8px 12px;width:100%;margin-bottom:20px;border-radius:4px;font-family:monospace;box-sizing:border-box}
.path{color:#8b949e;font-size:0.85em}
</style></head><body>
<h1>📖 Docs Browser</h1>
<input type="text" id="f" placeholder="Filtruj..." autofocus>
<ul id="l">
<?php foreach ($files as $f): ?>
<li><a href="?file=<?= urlencode($f) ?>"><?= htmlspecialchars(basename($f)) ?></a> <span class="path"><?= htmlspecialchars($f) ?></span></li>
<?php endforeach; ?>
</ul>
<script>
document.getElementById('f').addEventListener('input',function(){
  var q=this.value.toLowerCase();
  document.querySelectorAll('#l li').forEach(function(li){li.style.display=li.textContent.toLowerCase().includes(q)?'':'none';});
});
</script>
</body></html>
