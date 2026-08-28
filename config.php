<?php
/**
 * 配置文件
 * 成绩管理系统 - PHP 5.4 兼容
 */

// 数据文件路径
define('DATA_DIR', __DIR__ . '/data');
define('DATA_FILE', DATA_DIR . '/students.csv');

// 科目定义：键 => 显示名（满分统一 100）
$SUBJECTS = array(
    'chinese' => array('name' => '语文'),
    'math'    => array('name' => '数学'),
    'english' => array('name' => '英语'),
);
