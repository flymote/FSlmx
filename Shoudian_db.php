<?php
if (!defined('BYPASS_LOGIN') && empty($_SESSION['FSlmxusers'])){
	header("Location:./index.php");
	die("login first!!");
}

define("SYSDB_HOST",  'localhost');
define("SYSDB_USER", 'limx');
define("SYSDB_PASSWORD",'limaoxiang');
define("SYSDB_MAINDB",'shoudian');
define("SYSDB_FSDB",'freeswitch');

$mysqli = new mysqli(SYSDB_HOST, SYSDB_USER, SYSDB_PASSWORD, SYSDB_MAINDB);
// $mysqli = new mysqli('localhost', 'root', 'root', 'shoudian');
if ($mysqli->connect_error) {
    die('数据库 连接错误 (' . $mysqli->connect_errno . ') '. $mysqli->connect_error);
}
if (mysqli_connect_error()) {
    die('数据库 连接错误 (' . mysqli_connect_errno() . ') '. mysqli_connect_error());
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