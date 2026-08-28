<?php
/**
 * 删除学生记录（先确认，再删除）
 * GET  id=xxx  显示确认页
 * POST id=xxx  执行删除并跳回首页
 */
require_once __DIR__ . '/functions.php';
ensure_data_file();

$id = isset($_REQUEST['id']) ? trim($_REQUEST['id']) : '';
$students = load_students();
$index = find_student_index($students, $id);

if ($index === false) {
    header('Location: index.php');
    exit;
}

$student = $students[$index];

// ---- POST：确认删除 ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    unset($students[$index]);
    // 重建连续下标
    $students = array_values($students);
    save_students($students);
    header('Location: index.php?deleted=1');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>删除确认 - 成绩管理系统</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container container-narrow">
    <h1>删除确认</h1>

    <div class="confirm-box">
        <p>确定要删除以下学生的成绩记录吗？<strong>此操作不可恢复。</strong></p>
        <table class="score-table">
            <thead>
            <tr><th>学号</th><th>姓名</th><th>班级</th><th>语文</th><th>数学</th><th>英语</th></tr>
            </thead>
            <tbody>
            <tr>
                <td><?php echo e($student['id']); ?></td>
                <td><?php echo e($student['name']); ?></td>
                <td><?php echo e($student['class']); ?></td>
                <td><?php echo $student['chinese']; ?></td>
                <td><?php echo $student['math']; ?></td>
                <td><?php echo $student['english']; ?></td>
            </tr>
            </tbody>
        </table>

        <form method="post" action="delete.php" class="form-actions">
            <input type="hidden" name="id" value="<?php echo e($id); ?>">
            <button type="submit" class="btn btn-danger">确认删除</button>
            <a class="btn-link" href="index.php">取消</a>
        </form>
    </div>
</div>
</body>
</html>
