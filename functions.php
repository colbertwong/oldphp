<?php
/**
 * 公共函数库
 * 负责 CSV 数据的读写与成绩计算
 */

require_once __DIR__ . '/config.php';

/**
 * 首次运行时自动创建数据文件（含示例数据）
 */
function ensure_data_file() {
    if (file_exists(DATA_FILE)) {
        return;
    }
    $samples = array(
        array('2024001', '张三', '高一(1)班', 92, 88, 95),
        array('2024002', '李四', '高一(1)班', 78, 95, 82),
        array('2024003', '王五', '高一(2)班', 85, 76, 90),
        array('2024004', '赵六', '高一(2)班', 68, 91, 73),
        array('2024005', '钱七', '高一(1)班', 88, 84, 79),
    );
    $students = array();
    foreach ($samples as $row) {
        $students[] = array(
            'id'      => $row[0],
            'name'    => $row[1],
            'class'   => $row[2],
            'chinese' => $row[3],
            'math'    => $row[4],
            'english' => $row[5],
        );
    }
    save_students($students);
}

/**
 * 解析一行 CSV（轻量 RFC 4180 实现，支持引号包裹的字段）
 *
 * 说明：不依赖 fgetcsv/str_getcsv。某些 Windows 版本的 PHP
 * 在 locale 为 GBK 时，CSV 解析器会误吞 UTF-8 多字节字符
 * （第二字节为 0x8F，如"号""班"）后面的逗号，故自行实现。
 * 按字节扫描是安全的：逗号和双引号都是 ASCII 字节，
 * 不会与 UTF-8 多字节字符的续字节（>= 0x80）混淆。
 */
function csv_parse_line($line) {
    $fields = array();
    $field = '';
    $in_quotes = false;
    $len = strlen($line);
    for ($i = 0; $i < $len; $i++) {
        $c = $line[$i];
        if ($in_quotes) {
            if ($c === '"') {
                if ($i + 1 < $len && $line[$i + 1] === '"') {
                    $field .= '"'; // 引号转义 ""
                    $i++;
                } else {
                    $in_quotes = false;
                }
            } else {
                $field .= $c;
            }
        } else {
            if ($c === '"' && $field === '') {
                $in_quotes = true;
            } elseif ($c === ',') {
                $fields[] = $field;
                $field = '';
            } else {
                $field .= $c;
            }
        }
    }
    $fields[] = $field;
    return $fields;
}

/**
 * 转义一个 CSV 字段：含逗号/引号/换行时用双引号包裹
 */
function csv_escape_field($v) {
    $v = (string)$v;
    if (strpos($v, ',') !== false || strpos($v, '"') !== false
        || strpos($v, "\n") !== false || strpos($v, "\r") !== false) {
        return '"' . str_replace('"', '""', $v) . '"';
    }
    return $v;
}

/**
 * 读取全部学生数据
 * @return array 每个元素: id, name, class, chinese, math, english
 */
function load_students() {
    $students = array();
    if (!file_exists(DATA_FILE)) {
        return $students;
    }
    $lines = file(DATA_FILE);
    $first = true;
    foreach ($lines as $line) {
        $row = csv_parse_line(rtrim($line, "\r\n"));
        if ($first) {
            $first = false;
            // 跳过表头行（兼容带 BOM 的表头）
            if (isset($row[0]) && trim(ltrim($row[0], "\xEF\xBB\xBF")) === '学号') {
                continue;
            }
        }
        if (count($row) < 6) {
            continue; // 跳过空行或格式错误的行
        }
        $students[] = array(
            'id'      => trim($row[0]),
            'name'    => trim($row[1]),
            'class'   => trim($row[2]),
            'chinese' => (float)$row[3],
            'math'    => (float)$row[4],
            'english' => (float)$row[5],
        );
    }
    return $students;
}

/**
 * 保存全部学生数据（整表覆盖写）
 */
function save_students($students) {
    if (!is_dir(DATA_DIR)) {
        mkdir(DATA_DIR, 0777, true);
    }
    $lines = array();
    $lines[] = implode(',', array('学号', '姓名', '班级', '语文', '数学', '英语'));
    foreach ($students as $s) {
        $lines[] = implode(',', array_map('csv_escape_field', array(
            $s['id'], $s['name'], $s['class'],
            $s['chinese'], $s['math'], $s['english'],
        )));
    }
    // UTF-8 BOM（方便 Excel 打开）+ CRLF 行尾（兼容 Windows 记事本）
    file_put_contents(DATA_FILE, "\xEF\xBB\xBF" . implode("\r\n", $lines) . "\r\n");
}

/**
 * 按学号查找学生，返回数组下标
 * @return int|bool 找到返回下标，否则 false
 */
function find_student_index($students, $id) {
    foreach ($students as $i => $s) {
        if ($s['id'] === $id) {
            return $i;
        }
    }
    return false;
}

/**
 * 计算总分
 */
function compute_total($s) {
    return $s['chinese'] + $s['math'] + $s['english'];
}

/**
 * 计算平均分（保留 1 位小数）
 */
function compute_avg($s) {
    return round(compute_total($s) / 3, 1);
}

/**
 * 按总分降序排序，并计算排名（总分相同则并列）
 * 同时为每个学生附上 total / avg / rank 字段
 */
function sort_and_rank(&$students) {
    usort($students, 'compare_by_total');
    $rank = 0;
    $idx = 0;
    $prev_total = null;
    foreach ($students as &$s) {
        $idx++;
        $s['total'] = compute_total($s);
        $s['avg'] = compute_avg($s);
        if ($prev_total === null || $s['total'] != $prev_total) {
            $rank = $idx;
        }
        $s['rank'] = $rank;
        $prev_total = $s['total'];
    }
    unset($s);
}

/**
 * usort 回调：按总分降序
 */
function compare_by_total($a, $b) {
    $ta = compute_total($a);
    $tb = compute_total($b);
    return $tb <=> $ta;
}

/**
 * HTML 转义快捷函数
 */
function e($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * 从学生列表中提取所有班级（去重）
 */
function get_class_list($students) {
    $classes = array();
    foreach ($students as $s) {
        if (!in_array($s['class'], $classes, true)) {
            $classes[] = $s['class'];
        }
    }
    sort($classes);
    return $classes;
}
