<?php
/**
 * 生成mysql数据字典
 * 该版本适用于php高版本的导出,7.0以上可以使用
 */
header ( "Content-type: text/html; charset=utf-8" );

// 配置数据库
$dbserver = "XXX";
$dbusername = "XXX";
$dbpassword = "XXX";
$database = "XXX";

// 其他配置
$title = '数据字典';

$mysql_conn = @mysqli_connect ( "$dbserver", "$dbusername", "$dbpassword" ) or die ( "Mysql connect is error." );
mysqli_select_db ( $mysql_conn,$database);
mysqli_query ( $mysql_conn,'SET NAMES utf8' );
$table_result = mysqli_query (  $mysql_conn ,'show tables');
// 取得所有的表名
while ( $row = mysqli_fetch_array ( $table_result ) ) {
	$tables [] ['TABLE_NAME'] = $row [0];
}

// 循环取得所有表的备注及表中列消息
foreach ( $tables as $k => $v ) {
	$sql = 'SELECT * FROM ';
	$sql .= 'INFORMATION_SCHEMA.TABLES ';
	$sql .= 'WHERE ';
	$sql .= "table_name = '{$v['TABLE_NAME']}'  AND table_schema = '{$database}'";
	$table_result = mysqli_query ($mysql_conn, $sql );
	while ( $t = mysqli_fetch_array ( $table_result ) ) {
		$tables [$k] ['TABLE_COMMENT'] = $t ['TABLE_COMMENT'];
	}

	$sql = 'SELECT * FROM ';
	$sql .= 'INFORMATION_SCHEMA.COLUMNS ';
	$sql .= 'WHERE ';
	$sql .= "table_name = '{$v['TABLE_NAME']}' AND table_schema = '{$database}'";

	$fields = array ();
	$field_result = mysqli_query ( $mysql_conn,$sql);
	while ( $t = mysqli_fetch_array ( $field_result ) ) {
		$fields [] = $t;
	}
	$tables [$k] ['COLUMN'] = $fields;
}
mysqli_close ( $mysql_conn );

$html = '';
// 循环所有表
foreach ( $tables as $k => $v ) {
	// $html .= '<p><h2>'. $v['TABLE_COMMENT'] . '&nbsp;</h2>';
	$html .= '<table  border="1" cellspacing="0" cellpadding="0" align="center">';
	$html .= '<caption>' . $v ['TABLE_NAME'] . '  ' . $v ['TABLE_COMMENT'] . '</caption>';
	$html .= '<tbody><tr><th>字段名</th><th>数据类型</th><th>默认值</th>
    <th>允许非空</th>
    <th>自动递增</th><th>备注</th></tr>';
	$html .= '';

	foreach ( $v ['COLUMN'] as $f ) {
		$html .= '<tr><td class="c1">' . $f ['COLUMN_NAME'] . '</td>';
		$html .= '<td class="c2">' . $f ['COLUMN_TYPE'] . '</td>';
		$html .= '<td class="c3">&nbsp;' . $f ['COLUMN_DEFAULT'] . '</td>';
		$html .= '<td class="c4">&nbsp;' . $f ['IS_NULLABLE'] . '</td>';
		$html .= '<td class="c5">' . ($f ['EXTRA'] == 'auto_increment' ? '是' : '&nbsp;') . '</td>';
		$html .= '<td class="c6">&nbsp;' . $f ['COLUMN_COMMENT'] . '</td>';
		$html .= '</tr>';
	}
	$html .= '</tbody></table></p>';
}

// 输出
echo '<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>' . $title . '</title>
<style>
body,td,th {font-family:"宋体"; font-size:12px;}
table{border-collapse:collapse;border:1px solid #CCC;background:#6089D4;}
table caption{text-align:left; background-color:#fff; line-height:2em; font-size:14px; font-weight:bold; }
table th{text-align:left; font-weight:bold;height:26px; line-height:25px; font-size:16px; border:1px solid #000; color:#000000; padding:5px;}
table td{height:25px; font-size:12px; border:1px solid #000; background-color:#f0f0f0; padding:5px;}
.c1{ width: 150px;}
.c2{ width: 130px;}
.c3{ width: 70px;}
.c4{ width: 80px;}
.c5{ width: 80px;}
.c6{ width: 300px;}
</style>
</head>
<body>';
echo '<h1 style="text-align:center;">' . $title . '</h1>';
echo $html;
echo '</body></html>';

?>
