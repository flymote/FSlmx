<?php
define("SYSDB_HOST",  'localhost'); //mysql数据库主机
define("SYSDB_USER", 'root'); //mysql数据库用户名
define("SYSDB_PASSWORD",'root'); //mysql数据库密码
define("SYSDB_MAINDB",'shoudian'); //mysql数据库名，业务数据（本系统的数据库）
define("SYSDB_FSDB",'freeswitch'); //mysql数据库名，freeswitch数据库（这是FS用ODBC访问的运行数据库，需修改FS使用mysql数据库，而后在这里被系统调用）

$mysqli = new mysqli(SYSDB_HOST, SYSDB_USER, SYSDB_PASSWORD, SYSDB_MAINDB);
// $mysqli = new mysqli('localhost', 'root', 'root', 'shoudian');
if ($mysqli->connect_error) {
    die('数据库 连接错误 (' . $mysqli->connect_errno . ') '. $mysqli->connect_error);
}
$mysqli->query("set names UTF8");

//设置返回数据类型 MYSQLI_ASSOC, MYSQLI_NUM, or MYSQLI_BOTH. 
function result_fetch_all($result,$tag=MYSQLI_BOTH){
	if (empty($result))
		return false;
	$results = array();
	while (($row = $result->fetch_array($tag))!==false) {
		if (!$row) return $results;
		$results[] = $row;
	}
}

//建立于freeswitch的连接
function freeswitchDB(){
	$mysqli = new mysqli(SYSDB_HOST, SYSDB_USER, SYSDB_PASSWORD, SYSDB_FSDB);
	if ($mysqli->connect_error) {
		die('数据库 连接错误 (' . $mysqli->connect_errno . ') '. $mysqli->connect_error);
	}
	return $mysqli;
}
