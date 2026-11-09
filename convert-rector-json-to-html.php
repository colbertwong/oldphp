<?php
// convert-rector-json-to-html.php
$json = file_get_contents($argv[1] ?? 'rector-report.json');
$data = json_decode($json, true);

if (!$data) {
    die("无法解析 JSON 文件\n");
}

// ---- 统计规则使用次数 ----
$ruleStats = [];        // 规则 => 触发次数（文件×规则 计 1 次）
$ruleFiles = [];        // 规则 => 涉及的文件列表
foreach ($data['file_diffs'] ?? [] as $item) {
    $file = $item['file'] ?? '';
    foreach ($item['applied_rectors'] ?? [] as $rule) {
        if (!isset($ruleStats[$rule])) {
            $ruleStats[$rule] = 0;
            $ruleFiles[$rule] = [];
        }
        $ruleStats[$rule]++;
        if (!in_array($file, $ruleFiles[$rule], true)) {
            $ruleFiles[$rule][] = $file;
        }
    }
}
// 按次数降序排序
arsort($ruleStats);
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
        /* 规则统计表样式 */
        table { border-collapse: collapse; width: 100%; background: #fff; margin-bottom: 2em; }
        th, td { border: 1px solid #ddd; padding: 8px 12px; text-align: left; font-size: 0.9em; }
        th { background: #343a40; color: #fff; }
        tr:nth-child(even) { background: #f2f2f2; }
        .rule-name { font-family: monospace; word-break: break-all; }
        .count { text-align: center; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Rector 变更报告</h1>
    <div class="summary">
        <strong>变更文件数：</strong> <?= (int)($data['totals']['changed_files'] ?? 0) ?>
        &nbsp;|&nbsp;
        <strong>错误数：</strong> <?= (int)($data['totals']['errors'] ?? 0) ?>
        &nbsp;|&nbsp;
        <strong>触发规则种类：</strong> <?= count($ruleStats) ?>
    </div>

    <!-- 规则统计表 -->
    <h2>规则使用统计</h2>
    <?php if (empty($ruleStats)): ?>
        <p>没有规则触发记录。</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Rector 规则</th>
                    <th>触发次数</th>
                    <th>涉及文件数</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; foreach ($ruleStats as $rule => $count): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td class="rule-name"><?= htmlspecialchars($rule) ?></td>
                        <td class="count"><?= (int)$count ?></td>
                        <td class="count"><?= count($ruleFiles[$rule]) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- 文件规则一览 -->
    <h2>文件应用规则一览</h2>
    <?php if (empty($data['file_diffs'])): ?>
        <p>没有文件变更记录。</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>文件</th>
                    <th>应用的规则</th>
                    <th>规则数</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; foreach ($data['file_diffs'] as $item): ?>
                    <?php
                    $file = $item['file'] ?? '';
                    $rules = $item['applied_rectors'] ?? [];
                    ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td class="rule-name"><?= htmlspecialchars($file) ?></td>
                        <td>
                            <?php if (empty($rules)): ?>
                                <span style="color:#999;">（无规则记录）</span>
                            <?php else: ?>
                                <ul style="margin:0;padding-left:1.2em;">
                                    <?php foreach ($rules as $r): ?>
                                        <li class="rule-name"><?= htmlspecialchars($r) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </td>
                        <td class="count"><?= count($rules) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    
    <!-- 文件变更详情 -->
    <h2>文件变更详情</h2>
    <?php foreach ($data['file_diffs'] ?? [] as $item): ?>
        <div class="file">
            <h3><?= htmlspecialchars($item['file']) ?></h3>
            <div class="rectors">
                规则：<?= htmlspecialchars(implode(', ', $item['applied_rectors'] ?? [])) ?>
            </div>
            <pre><?php
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
