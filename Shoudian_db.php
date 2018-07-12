<?php
$mysqli = new mysqli('localhost', 'root', 'root', 'FSlmx');
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