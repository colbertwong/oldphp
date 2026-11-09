<?php
// convert-rector-json-to-html.php
$json = file_get_contents($argv[1] ?? 'rector-report.json');
$data = json_decode($json, true);

if (!$data) {
    die("无法解析 JSON 文件\n");
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Rector 变更报告</title>
    <style>
        body { font-family: sans-serif; margin: 2em; background: #f8f9fa; }
        .summary { background: #fff; padding: 1em; border-radius: 8px; margin-bottom: 2em; }
        .file { background: #fff; margin-bottom: 2em; padding: 1em; border-radius: 8px; }
        .file h3 { margin-top: 0; color: #333; }
        .rectors { font-size: 0.85em; color: #666; margin-bottom: 1em; }
        pre { background: #272822; color: #f8f8f2; padding: 1em; overflow-x: auto; border-radius: 4px; }
        .diff-line-add { color: #a6e22e; }
        .diff-line-del { color: #f92672; }
    </style>
</head>
<body>
    <h1>Rector 变更报告</h1>
    <div class="summary">
        <strong>变更文件数：</strong> <?= (int)($data['totals']['changed_files'] ?? 0) ?>
        &nbsp;|&nbsp;
        <strong>错误数：</strong> <?= (int)($data['totals']['errors'] ?? 0) ?>
    </div>

    <?php foreach ($data['file_diffs'] ?? [] as $item): ?>
        <div class="file">
            <h3><?= htmlspecialchars($item['file']) ?></h3>
            <div class="rectors">
                规则：<?= htmlspecialchars(implode(', ', $item['applied_rectors'] ?? [])) ?>
            </div>
            <pre><?php
                // 简单高亮 diff 的增删行
                $lines = explode("\n", $item['diff'] ?? '');
                foreach ($lines as $line) {
                    $class = '';
                    if (strpos($line, '+') === 0 && strpos($line, '+++') !== 0) $class = 'diff-line-add';
                    elseif (strpos($line, '-') === 0 && strpos($line, '---') !== 0) $class = 'diff-line-del';
                    echo $class ? "<span class=\"$class\">" . htmlspecialchars($line) . "</span>\n" : htmlspecialchars($line) . "\n";
                }
            ?></pre>
        </div>
    <?php endforeach; ?>
</body>
</html>