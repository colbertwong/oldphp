<?php
/**
 * 新增 / 编辑学生成绩
 * GET  id=xxx   编辑该学生
 * GET  （无 id） 新增学生
 * POST          保存（保存成功后跳回首页）
 */
require_once __DIR__ . '/functions.php';
ensure_data_file();

$students = load_students();

// ---- 确定编辑还是新增 ----
$edit_id = isset($_GET['id']) ? trim($_GET['id']) : '';
$edit_index = false;
if ($edit_id !== '') {
    $edit_index = find_student_index($students, $edit_id);
    if ($edit_index === false) {
        header('Location: index.php');
        exit;
    }
}

// ---- 表单默认值 ----
$form = array(
    'name'    => '',
    'class'   => '',
    'chinese' => '',
    'math'    => '',
    'english' => '',
);
if ($edit_index !== false) {
    $form['name']    = $students[$edit_index]['name'];
    $form['class']   = $students[$edit_index]['class'];
    $form['chinese'] = $students[$edit_index]['chinese'];
    $form['math']    = $students[$edit_index]['math'];
    $form['english'] = $students[$edit_index]['english'];
}

$errors = array();

// ---- 处理保存 ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['name']    = isset($_POST['name']) ? trim($_POST['name']) : '';
    $form['class']   = isset($_POST['class']) ? trim($_POST['class']) : '';
    $form['chinese'] = isset($_POST['chinese']) ? trim($_POST['chinese']) : '';
    $form['math']    = isset($_POST['math']) ? trim($_POST['math']) : '';
    $form['english'] = isset($_POST['english']) ? trim($_POST['english']) : '';

    // 校验
    if ($form['name'] === '') {
        $errors[] = '姓名不能为空';
    }
    if ($form['class'] === '') {
        $errors[] = '班级不能为空';
    }
    foreach (array_keys($SUBJECTS) as $sub) {
        $v = $form[$sub];
        if (!is_numeric($v) || $v < 0 || $v > 100) {
            $errors[] = $SUBJECTS[$sub]['name'] . '成绩必须是 0~100 之间的数字';
        }
    }

    if (count($errors) === 0) {
        if ($edit_index !== false) {
            // 更新现有记录（学号不变）
            $students[$edit_index]['name']    = $form['name'];
            $students[$edit_index]['class']   = $form['class'];
            $students[$edit_index]['chinese'] = (float)$form['chinese'];
            $students[$edit_index]['math']    = (float)$form['math'];
            $students[$edit_index]['english'] = (float)$form['english'];
        } else {
            // 新增：学号 = 当前最大学号 + 1
            $max_id = 0;
            foreach ($students as $s) {
                if (is_numeric($s['id']) && (int)$s['id'] > $max_id) {
                    $max_id = (int)$s['id'];
                }
            }
            $students[] = array(
                'id'      => (string)($max_id + 1),
                'name'    => $form['name'],
                'class'   => $form['class'],
                'chinese' => (float)$form['chinese'],
                'math'    => (float)$form['math'],
                'english' => (float)$form['english'],
            );
        }
        save_students($students);
        header('Location: index.php?saved=1');
        exit;
    }
}

// ---- 新增模式：预览自动生成的学号 ----
$new_id = '';
if ($edit_index === false) {
    $max_id = 0;
    foreach ($students as $s) {
        if (is_numeric($s['id']) && (int)$s['id'] > $max_id) {
            $max_id = (int)$s['id'];
        }
    }
    $new_id = (string)($max_id + 1);
}

$page_title = ($edit_index !== false) ? '编辑学生成绩' : '添加学生';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title><?php echo e($page_title); ?> - 成绩管理系统</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container container-narrow">
    <h1><?php echo e($page_title); ?></h1>

    <?php if (count($errors) > 0): ?>
        <div class="flash-err">
            <?php foreach ($errors as $err): ?>
                <p><?php echo e($err); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="edit.php<?php if ($edit_index !== false) echo '?id=' . urlencode($edit_id); ?>" class="edit-form">
        <label>学号</label>
        <?php if ($edit_index !== false): ?>
            <input type="text" value="<?php echo e($edit_id); ?>" disabled>
            <p class="hint">学号不可修改</p>
        <?php else: ?>
            <input type="text" value="<?php echo e($new_id); ?>" disabled>
            <p class="hint">学号自动生成</p>
        <?php endif; ?>

        <label>姓名 *</label>
        <input type="text" name="name" value="<?php echo e($form['name']); ?>" maxlength="50">

        <label>班级 *</label>
        <input type="text" name="class" value="<?php echo e($form['class']); ?>" maxlength="50" placeholder="如：高一(1)班">

        <?php foreach (array_keys($SUBJECTS) as $sub): ?>
            <label><?php echo e($SUBJECTS[$sub]['name']); ?> 成绩 *（0~100）</label>
            <input type="text" name="<?php echo $sub; ?>" value="<?php echo e($form[$sub]); ?>" maxlength="5">
        <?php endforeach; ?>

        <div class="form-actions">
            <button type="submit" class="btn">保存</button>
            <a class="btn-link" href="index.php">返回列表</a>
        </div>
    </form>
</div>
</body>
</html>
