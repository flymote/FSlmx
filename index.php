<?php
define('APPID', 'FSlmx');
set_time_limit(600);
session_start();
date_default_timezone_set('Asia/Shanghai');
header("Content-type: text/html; charset=utf-8");
define('IS_WIN',strstr(PHP_OS, 'WIN') ? 1 : 0 );

require_once "Shoudian_db.php";
$result = $mysqli->query("select * from fs_setting where `enabled` = 9 limit 1");
$row = $result->fetch_array();
if (empty($row)){
	$showinfo = "<span style='font-size:16pt;color:red;'>未设定主控，请先设置主控服务器！</span>";
	define("ESL_HOST", "localhost");
	define("ESL_PORT", 8021);
	define("ESL_PASSWORD", 'ClueCon');
	if (IS_WIN)
		$_SESSION['conf_dir'] = "d://freeswitch//conf";
	else 
		$_SESSION['conf_dir'] = "/etc/freeswitch";
}else{
	define("ESL_HOST", $row['ESL_host']);
	define("ESL_PORT", $row['ESL_port']);
	define("ESL_PASSWORD",$row['ESL_password']);
	$showinfo = "<span style='font-size:20pt;color:gray;'>@".ESL_HOST."</span>";
	$_SESSION['log_dir'] = $row['log_dir'];
	$_SESSION['conf_dir'] = $row['conf_dir'];
	$_SESSION['recordings_dir'] = $row['recordings_dir'];
	$_SESSION['ESL_HOST'] = ESL_HOST;
	$_SESSION['ESL_PORT'] = ESL_PORT;
	$_SESSION['ESL_PASSWORD'] = ESL_PASSWORD;
	$_SESSION['xmlcdr_auth'] = array(); //如果设置有值：array('user','password')，即为使用认证，将启用xmlcdr配置文件中的用户认证
}
require_once "detect_switch.php";
echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type content=text/html;charset=utf-8"/><link rel="stylesheet" type="text/css" href="main.css"/></head><body>';
echo "<p class='pcenter' style='font-size:22pt;'>FreeSwitch 控制台 $showinfo <a href='FS_setting.php' style='font-size:10pt;'>【服务器设置】</a> <a href='Inconfig.php' style='font-size:10pt;'>【参数设置】</a> <a href='esl_cmd.php' style='font-size:10pt;'>【ESL控制】</a></p>";
echo "<p class='pleft'><span style='font-size:14pt;color:gray'> ☏  </span> 查看：【<a href='FS_xmlcdr_list.php'>CDR记录</a>】【<a href='?'>FS运行信息</a>】【<a href='?channels=1'>查看channels</a>】【<a href='?calls=1'>查看Calls</a>】
<br/><span style='font-size:14pt;color:#FF8C00'> ☏  </span> 加载：【<a href='cdr_post.php'>本地CDR</a>】 【<a href='?reloadxml=1'>reloadxml</a>】 【<a href='?reloadsofia=1'>reload mod_sofia</a>】 【<a href='?reloadxmlcdr=1'>reload xml_cdr</a>】 【<a href='?reloadacl=1'>reloadacl</a>】 【<a href='?restart=1'>重启".ESL_HOST."</a>】
<br/><br/>=========下面的管理操作是基于本机系统配置文件的管理：===========================================
<br/><span style='font-size:14pt;color:#FF8C00'> ☎  </span> 系统管理：【<a href='FS_freeswitch_cp.php'>系统配置</a>】【<a href='FS_switch_cp.php'>系统参数配置</a>】【<a href='FS_vars_cp.php'>系统预处理参数配置</a>】【<a href='FS_modules_cp.php'>系统功能模块配置</a>】
<br/><span style='font-size:14pt;color:#FF8C00'> ☎  </span> SIP管理：【<a href='FS_sofiaExternal_cp.php'>sofiaExternal管理</a>】【<a href='FS_sofiaInternal_cp.php'>sofiaInternal管理</a>】【<a href='FS_gateways_cp.php'>路由管理</a>】【<a href='FS_xmlcdr_cp.php'>呼叫详细记录xmlcdr配置</a>】【<a href='FS_acl_cp.php'>访问控制列表acl配置</a>】
<br/><span style='font-size:14pt;color:#FF8C00'> ☎  </span> 拨号管理：【<a href='FS_files_edit.php'>配置文件管理</a>】【<a href='FS_extensions_cp.php'>extensions管理</a>】【<a href='FS_dialplans_cp.php'>dialplans管理</a>】
<br/><span style='font-size:14pt;color:#FF8C00'> ☎  </span> 域及用户管理：【<a href='FS_domains_cp.php'>域管理控制台</a>】【<a href='FS_groups_cp.php'>用户组管理</a>】【<a href='FS_users_cp.php'>用户管理</a>】【<a href='FS_callcenter_cp.php'>呼叫中心管理</a>】</p>";

$info = new detect_switch();

//下面 run 中 apireload 是使用api reload；reload 是使用bgapi reload；其他命令仅是sofia 和 show 可以带自定义的参数

if (isset($_GET['restart'])){
	$info-> restart_switch();
}elseif (isset($_GET['reloadxml'])){
	$info-> run('reloadxml');
}elseif (isset($_GET['reloadacl'])){
	$info-> run('reloadacl');
}elseif (isset($_GET['reloadsofia'])){
	$info-> run('apireload','mod_sofia');
}elseif (isset($_GET['reloadxmlcdr'])){
	$info-> run('apireload','mod_xml_cdr');
}elseif (isset($_GET['channels'])){
	$info-> run('channels');
}elseif (isset($_GET['calls'])){
	$info-> run('calls');
}else 
	$info-> list_switch_info();
echo "</body></html>";