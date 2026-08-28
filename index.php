<?php
/**
 * 首页：学生成绩列表 + 统计
 */
require_once __DIR__ . '/functions.php';
ensure_data_file();

// ---- 读取查询参数 ----
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$class_filter = isset($_GET['class']) ? trim($_GET['class']) : '';

// ---- 加载并筛选数据 ----
$students = load_students();
$filtered = array();
foreach ($students as $s) {
    // 班级筛选
    if ($class_filter !== '' && $s['class'] !== $class_filter) {
        continue;
    }
    // 按姓名或学号模糊搜索
    if ($q !== '') {
        if (stripos($s['name'], $q) === false && stripos($s['id'], $q) === false) {
            continue;
        }
    }
    $filtered[] = $s;
}
sort_and_rank($filtered);
$class_list = get_class_list($students);

// ---- 统计（基于筛选后的数据） ----
$stats = array();
foreach (array_keys($SUBJECTS) as $sub) {
    $vals = array();
    foreach ($filtered as $s) {
        $vals[] = $s[$sub];
    }
    $stats[$sub] = array(
        'avg' => count($vals) > 0 ? round(array_sum($vals) / count($vals), 1) : '-',
        'max' => count($vals) > 0 ? max($vals) : '-',
        'min' => count($vals) > 0 ? min($vals) : '-',
    );
}
$count = count($filtered);

// ---- 提示消息 ----
$flash = '';
if (isset($_GET['saved']) && $_GET['saved'] == 1) {
    $flash = '数据已保存成功！';
} elseif (isset($_GET['deleted']) && $_GET['deleted'] == 1) {
    $flash = '记录已删除！';
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>成绩管理系统</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>成绩管理系统</h1>

    <?php if ($flash !== ''): ?>
        <p class="flash-ok"><?php echo e($flash); ?></p>
    <?php endif; ?>

    <!-- 工具栏：搜索 + 班级筛选 + 新增 -->
    <div class="toolbar">
        <form method="get" action="index.php" class="search-form">
            <input type="text" name="q" value="<?php echo e($q); ?>" placeholder="按姓名或学号搜索">
            <select name="class">
                <option value="">全部班级</option>
                <?php foreach ($class_list as $c): ?>
                    <option value="<?php echo e($c); ?>"<?php if ($class_filter === $c) echo ' selected'; ?>><?php echo e($c); ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit">查询</button>
            <?php if ($q !== '' || $class_filter !== ''): ?>
                <a class="btn-link" href="index.php">清除筛选</a>
            <?php endif; ?>
        </form>
        <a class="btn btn-add" href="edit.php">+ 添加学生</a>
    </div>

    <!-- 统计卡片 -->
    <div class="stats">
        <div class="stat-card"><span class="stat-num"><?php echo $count; ?></span><span class="stat-label">学生人数</span></div>
        <?php foreach ($SUBJECTS as $sub => $info): ?>
            <div class="stat-card">
                <span class="stat-label"><?php echo e($info['name']); ?> 平均分</span>
                <span class="stat-num"><?php echo $stats[$sub]['avg']; ?></span>
                <span class="stat-sub">最高 <?php echo $stats[$sub]['max']; ?> / 最低 <?php echo $stats[$sub]['min']; ?></span>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- 成绩表格 -->
    <table class="score-table">
        <thead>
        <tr>
            <th>排名</th>
            <th>学号</th>
            <th>姓名</th>
            <th>班级</th>
            <?php foreach ($SUBJECTS as $sub => $info): ?>
                <th><?php echo e($info['name']); ?></th>
            <?php endforeach; ?>
            <th>总分</th>
            <th>平均分</th>
            <th>操作</th>
        </tr>
        </thead>
        <tbody>
        <?php if ($count === 0): ?>
            <tr><td colspan="10" class="empty">暂无数据</td></tr>
        <?php endif; ?>
        <?php foreach ($filtered as $s): ?>
            <tr>
                <td class="rank"><?php echo $s['rank']; ?></td>
                <td><?php echo e($s['id']); ?></td>
                <td><?php echo e($s['name']); ?></td>
                <td><?php echo e($s['class']); ?></td>
                <?php foreach (array_keys($SUBJECTS) as $sub): ?>
                    <td class="<?php echo ($s[$sub] < 60) ? 'fail' : ''; ?>"><?php echo $s[$sub]; ?></td>
                <?php endforeach; ?>
                <td class="total"><?php echo $s['total']; ?></td>
                <td><?php echo $s['avg']; ?></td>
                <td class="actions">
                    <a href="edit.php?id=<?php echo urlencode($s['id']); ?>">编辑</a>
                    <a class="del" href="delete.php?id=<?php echo urlencode($s['id']); ?>">删除</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <p class="footer">数据存储于 data/students.csv，可用 Excel 直接打开编辑（编辑后请保持表头格式不变）。</p>
</div>
</body>
</html>
