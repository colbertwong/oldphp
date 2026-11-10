<? php

// $str !== '' —— 空字符串直接跳过。
// !mb_check_encoding($str, 'UTF-8') —— 已经是合法 UTF-8 的字符串跳过，这是防止误转换的关键一步。
// mb_check_encoding($str, 'EUC-JP') —— 只有确实是合法 EUC-JP 时才进入转换。
if ($str !== '' && !mb_check_encoding($str, 'UTF-8') && mb_check_encoding($str, 'EUC-JP')) {
  $str = mb_convert_encoding($str, 'UTF-8', 'EUC-JP');
}
