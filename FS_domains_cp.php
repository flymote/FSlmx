<?php
 set_time_limit(600);
 session_start(); 
 date_default_timezone_set('Asia/Shanghai');
header("Content-type: text/html; charset=utf-8");

define("ESL_HOST", @$_SESSION['ESL_HOST']);
define("ESL_PORT", @$_SESSION['ESL_PORT']);
define("ESL_PASSWORD",@$_SESSION['ESL_PASSWORD']);

include 'Shoudian_db.php';
//-------------------修改或添加域信息-----------------------------------
if (isset($_GET['editDomain'])){
	$id = intval($_GET['editDomain']);
	$showinfo = "";
	if ($id){
		$result = $mysqli->query("select * from fs_domains where id = $id");
		$sql = "update fs_domains set ";
		$sql_end = " where id = $id";
		$showinfo .=" id $id 更新 ";
	}else{
		$result = false;
		$sql = "insert into fs_domains (`domain_id`,`domain_name`,`level`,`parent_id`,`create_date`,`last_date`,`user_prefix`,`group_prefix`) values(";
		$sql_end = " )";
		$showinfo .=" 添加 ";
}

$fail = 0;
if ($result)
	$row = $result->fetch_array();
else 
	$row = array();

$ext_result = $mysqli->query("select `id`,`domain_id`,`parent_id`,`enabled`,`domain_name` from fs_domains order by id DESC");
$domains = result_fetch_all($ext_result,MYSQLI_NUM);
$domain_up = "<option value=''>[无上级域]</option>";
$domain_lists = array();
foreach ($domains as $one){
	$domain_lists[$one[0]] = $one[1];
	if ($one[0] != $id && $one[3])
		$domain_up .= "<option value='$one[0] $one[1]'>[$one[0]] $one[4]</option>";
}
unset($domains);

if (!empty($_POST)){
	$domain_name = $_POST['domain_name'];
	$group_prefix = intval($_POST['group_prefix']);
	$user_prefix = intval($_POST['user_prefix']);
	$domain_id = $_POST['domain_id'];
	if (empty($domain_id) || empty($domain_name)) {
		$showinfo .= "<span class='bgred'>必须提交域名及域标识！</span><br/>";
		$fail = 1;
	}
	if (!($group_prefix && $user_prefix && $group_prefix != $user_prefix && strlen($group_prefix)<5 && strlen($user_prefix)<5)) {
		$showinfo .= "<span class='bgred'>用户前缀和组前缀须设置为最多4位数字，且不能为0 不能相同！</span><br/>";
		$fail = 1;
	}
	$validRegExp =  '/^[a-z0-9\-\_\.]+$/';
	$prefixlen = strlen($_POST['domain_id']);
	if ($prefixlen && ($prefixlen>100 || !preg_match($validRegExp, $_POST['domain_id']))) {
		$showinfo .= "<span class='bgred'>域标识必须是小写字母数字！且不得超过100位，请修改域名称</span><br/>";
		$fail = 1;
		$prefix = "";
	}
	if ($id==0 && array_search($domain_id, $domain_lists)!==false){
		$showinfo .= "<span class='bgred'>域标识必须唯一，请修改域名称</span><br/>";
		$fail = 1;
	}
	$domain_name = $mysqli->real_escape_string($domain_name);
	$domain_id = $mysqli->real_escape_string($domain_id);
	if (!empty($_POST['parent_id'])){
		$temp = explode(" ", $_POST['parent_id']);
		$parent_id = intval($temp[0]);
		$domain_up = $_POST['parent_id'];
	}else {
		$domain_up = " <span class=\"smallgray smallsize-font\"> *无上级域* </span>";
		$parent_id = 0;
	}
	$level = intval($_POST['level']);
	if ($level>120)
		$level = 120;
	elseif ($level<0)
		$level = 0;

	if (isset($_POST['gw']))
		$gwname="'".implode("','", $_POST['gw'])."'";
	else 
		$gwname ="";

	$change_user = 0;
	if ($id && ($_POST['domain_id']!=@$row['domain_id'])){
		$change_user = 1;
		$olddid = @$row['domain_id'];
		$showinfo .= "<span class='bgblue'>域标识已经修改！将同步修改相关数据！</span><br/>";
	}
	
	if ($id)
		$sql .= "`domain_id`='$domain_id',`domain_name`='$domain_name',`level`=$level,`parent_id`=$parent_id,`last_date`=now(),`user_prefix`='$user_prefix',`group_prefix`='$group_prefix'";
	else
		$sql .= "'$domain_id','$domain_name',$level,'$parent_id',now(),now(),'$user_prefix','$group_prefix'";
	$gwold= $dmold ="";
}else{
	$domain_name = @$row['domain_name'];
	$domain_id = @$row['domain_id'];
	$level = (@$row['level']?$row['level']:50);
	$user_prefix = (@$row['user_prefix']?$row['user_prefix']:88);
	$group_prefix = (@$row['group_prefix']?$row['group_prefix']:99);
	$parent_id = intval(@$row['parent_id']);
	$gwname = $gwold = "";
	$dmold = $domain_name.$domain_id.$level.$user_prefix.$parent_id.$group_prefix;
	$domain_up = "<select name='parent_id' id='parent_id' class='inputline1'>$domain_up</select><script>";
	if ($parent_id)
		$domain_up .= "$('#parent_id').val('$parent_id $domain_lists[$parent_id]');</script>";
	else 
		$domain_up .="$('#parent_id').val('');</script>";
	$gwold = "<input type=\"hidden\" name=\"dmold\" value=\"$dmold\">";
}
$html = <<<HTML
<tr class='bg1'><td width=80><em>域名称：</em></td><td><input id="domain_name" name="domain_name" size="30"  maxlength="20" value="$domain_name" onclick="this.select();" class="inputline1"/> <span class="smallgray smallsize-font"> * 长度不得超过20，可中英文，不得重复</span></td></tr>
<tr class='bg2'><td>✦<em>域标识：</em></td><td><input id="domain_id" name="domain_id" value="$domain_id" size=80 class="inputline1" readonly="readonly" /> <span class="smallgray smallsize-font"> * 不可编辑，请点击 &raquo; <button type='button' onclick="getinfo($('#domain_name').val());">由域名称生成标识</button></span></td></tr>
<tr class='bg1'><td><em>上级域：</em></td><td>$domain_up <span class="smallgray smallsize-font"> * 若是下级域，请选择其上级的域；否则忽略之</span></td></tr>
<tr class='bg2'><td><em>信息项</em>$gwold</td><td><em>level</em> <input id="level" name="level" value="$level" size=2 class="inputline1" /> &nbsp; <em>用户前缀</em> <input id="user_prefix" name="user_prefix" value="$user_prefix" size=4 maxlength="4" class="inputline1" /> &nbsp; <em>组前缀</em> <input id="group_prefix" name="group_prefix" value="$group_prefix" size=4  maxlength="4" class="inputline1" /><span class="smallgray smallsize-font"> * 用户前缀和组前缀：用来在拨号时区分用户和组的前缀数字，不得相同，不得为空</span></td></tr>
HTML;
$submitbutton = "<input type=\"submit\" value=\"确认提交\" />";
if (!empty($_POST)){
	$submitbutton = ' <a href="?editDomain='.$id.'">刷新页面</a>';
	$sql  .= $sql_end;
	$result = false;
	if ($domain_name.$domain_id.$level.$user_prefix.$parent_id.$group_prefix==$_POST['dmold']){
		$showinfo .= "<span class='bgblue'>未修改数据不会提交更新！</span><br/>";
		$result = 1;
	}elseif (!$fail)
		$result = $mysqli->query($sql);
	if ($result){
		$showinfo .= "<span class='bggreen'>操作成功！</span>";
		if ($change_user){
			$mysqli->query("update fs_gateways set `domain_id`= '$domain_id' where `domain_id`='$olddid' ");
			$mysqli->query("update fs_users set `domain_id`='$domain_id' where  `domain_id`='$olddid' ");
			$mysqli->query("update fs_groups set `domain_id`='$domain_id' where  `domain_id`='$olddid' ");
		}
	}else
		$showinfo .= "<span class='bgred'>操作失败！{$mysqli->error}</span>";
}
echo <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head>
<meta http-equiv="Content-Type content=text/html;charset=utf-8"/>
 <link rel="stylesheet" type="text/css" href="main.css"/><script type="text/javascript" src="jquery.js"></script>
<script>function getinfo(sid){if (sid=='') {alert ('没有填写域名称，请先填写域名称！');}else \$.post( "Yurun/get_py.php", {string: sid}).done(function( data ) { $('#domain_id').val(data);});}</script>
</head><body><p class='pcenter' style='font-size:18pt;'>域详细信息设置 <a style='font-size:10pt;' href='?'>&raquo;&nbsp;返回域主控页</a></p><table class="tablegreen" width="1000" align="center"><form method="post"><th colspan=2>$showinfo</th>$html<tr class='bg1'><th></th><th>$submitbutton</th></tr></form></table></body></html>
HTML;
	exit;
}
//-----------域管理-------域数据库 ---POST提交操作-----------------------------
$ext_result = $mysqli->query("select `domain_name`,`id` from fs_domains");
$exts = result_fetch_all($ext_result,MYSQLI_NUM);
$dmlist = array();
foreach ($exts as $one)
	$dmlist[$one[1]] = $one[0];
//应用部署及停用，ESL
if (empty($_SESSION['POST_submit_once']) && isset($_POST['yid'])){
	if (in_array($_POST['en0'],array("88","99"))) {
		$name = $_POST['en1'];
		require_once "detect_switch.php";
		$result = $mysqli->query("select * from fs_domains where domain_id = '$name' and `enabled`=1");
		$row = $result->fetch_array();
		$file_dir = @$_SESSION['conf_dir']."/directory/".$row['domain_id'].".xml";
		$file_dia = @$_SESSION['conf_dir']."/dialplan/".$row['domain_id'].".xml";
		$file_diadir = @$_SESSION['conf_dir']."/dialplan/".$row['domain_id'];
		if (empty($row['domain_id']))
			die("操作域不可用！请先启用！");
		$_SESSION['POST_submit_once']=1;
		if ($_POST['en0']=="99" && is_file($file_dir)){
			$result = @unlink($file_dir);
			if ($result){
				@unlink($file_dia);
				$info = new detect_switch();
				$info->run('reloadxml','',0);
				die(" $name 域已被停用！");
			}else 
				die("$name 域数据无法清除，无法停用！");
		}else{
			$ext_result = $mysqli->query("select * from fs_gateways where `enabled`=0");
			$exts = result_fetch_all($ext_result);
			$gwlist = array();
			foreach ($exts as $one)
				$gwlist[$one['gatewayname']] = $one;
			$context ="<include>\n<context name=\"$row[domain_id]\">\n<extension name=\"unloop\">\n<condition field=\"\${unroll_loops}\" expression=\"^true$\"/>\n<condition field=\"\${sip_looped_call}\" expression=\"^true$\">\n<action application=\"deflect\" data=\"\${destination_number}\"/>\n</condition>\n</extension>\n<extension name=\"group-intercept\">\n<condition field=\"destination_number\" expression=\"^\*8$\">\n<action application=\"answer\"/>\n<action application=\"intercept\" data=\"\${hash(select/\${domain_name}-last_dial_ext/\${callgroup})}\"/>\n<action application=\"sleep\" data=\"2000\"/>\n</condition>\n</extension>\n<extension name=\"global\" continue=\"true\">\n<condition field=\"\${call_debug}\" expression=\"^true$\" break=\"never\">\n<action application=\"info\"/>\n</condition>\n<condition field=\"\${rtp_has_crypto}\" expression=\"^(\$\${rtp_sdes_suites})$\" break=\"never\">\n<action application=\"set\" data=\"rtp_secure_media=true\"/>\n<!-- Offer SRTP on outbound legs if we have it on inbound. -->\n<!-- <action application=\"export\" data=\"rtp_secure_media=true\"/> -->\n</condition>\n<condition field=\"\${endpoint_disposition}\" expression=\"^(DELAYED NEGOTIATION)\"/>\n<condition field=\"\${switch_r_sdp}\" expression=\"(AES_CM_128_HMAC_SHA1_32|AES_CM_128_HMAC_SHA1_80)\" break=\"never\">\n<action application=\"set\" data=\"rtp_secure_media=true\"/>\n<!-- Offer SRTP on outbound legs if we have it on inbound. -->\n<!-- <action application=\"export\" data=\"rtp_secure_media=true\"/> -->\n</condition>\n<condition>\n<action application=\"hash\" data=\"insert/\${domain_name}-spymap/\${caller_id_number}/\${uuid}\"/>\n<action application=\"hash\" data=\"insert/\${domain_name}-last_dial/\${caller_id_number}/\${destination_number}\"/>\n<action application=\"hash\" data=\"insert/\${domain_name}-last_dial/global/\${uuid}\"/>\n<action application=\"export\" data=\"RFC2822_DATE=\${strftime(%a, %d %b %Y %T %z)}\"/>\n</condition>\n</extension>\n<extension name=\"Local_Extension\">\n<condition field=\"destination_number\" expression=\"^$row[user_prefix](\d{1,20})$\">\n<action application=\"export\" data=\"dialed_extension=$1\"/>\n<!-- bind_meta_app can have these args <key> [a|b|ab] [a|b|o|s] <app> -->\n<action application=\"bind_meta_app\" data=\"1 b s execute_extension::dx XML features\"/>\n<action application=\"bind_meta_app\" data=\"2 b s record_session::\$\${recordings_dir}/\${caller_id_number}.\${strftime(%Y-%m-%d-%H-%M-%S)}.wav\"/>\n<action application=\"bind_meta_app\" data=\"3 b s execute_extension::cf XML features\"/>\n<action application=\"bind_meta_app\" data=\"4 b s execute_extension::att_xfer XML features\"/>\n<action application=\"set\" data=\"ringback=\${us-ring}\"/>\n<action application=\"set\" data=\"transfer_ringback=\$\${hold_music}\"/>\n<action application=\"set\" data=\"call_timeout=30\"/>\n<!-- <action application=\"set\" data=\"sip_exclude_contact=\${network_addr}\"/> -->\n<action application=\"set\" data=\"hangup_after_bridge=true\"/>\n<!--<action application=\"set\" data=\"continue_on_fail=NORMAL_TEMPORARY_FAILURE,USER_BUSY,NO_ANSWER,TIMEOUT,NO_ROUTE_DESTINATION\"/> -->\n<action application=\"set\" data=\"continue_on_fail=true\"/>\n<action application=\"hash\" data=\"insert/\${domain_name}-call_return/\${dialed_extension}/\${caller_id_number}\"/>\n<action application=\"hash\" data=\"insert/\${domain_name}-last_dial_ext/\${dialed_extension}/\${uuid}\"/>\n<action application=\"set\" data=\"called_party_callgroup=\${user_data(\${dialed_extension}@\${domain_name} var callgroup)}\"/>\n<action application=\"hash\" data=\"insert/\${domain_name}-last_dial_ext/\${called_party_callgroup}/\${uuid}\"/>\n<action application=\"hash\" data=\"insert/\${domain_name}-last_dial_ext/global/\${uuid}\"/>\n<!--<action application=\"export\" data=\"nolocal:rtp_secure_media=\${user_data(\${dialed_extension}@\${domain_name} var rtp_secure_media)}\"/>-->\n<action application=\"hash\" data=\"insert/\${domain_name}-last_dial/\${called_party_callgroup}/\${uuid}\"/>\n<action application=\"bridge\" data=\"user/\${dialed_extension}@\${domain_name}\"/>\n<action application=\"answer\"/>\n<action application=\"sleep\" data=\"1000\"/>\n<action application=\"bridge\" data=\"loopback/app=voicemail:default \${domain_name} \${dialed_extension}\"/>\n</condition>\n</extension>\n <X-PRE-PROCESS cmd=\"include\" data=\"$row[domain_id]/*.xml\"/>";
			$xml = "<include>\n<domain name=\"$row[domain_id]\">\n<params>\n<param name=\"dial-string\" value=\"{^^:sip_invite_domain=\${dialed_domain}:presence_id=\${dialed_user}@\${dialed_domain}}\${sofia_contact(*/\${dialed_user}@\${dialed_domain})},\${verto_contact(\${dialed_user}@\${dialed_domain})}\"/>\n<!-- These are required for Verto to function properly -->\n<param name=\"jsonrpc-allowed-methods\" value=\"verto\"/>\n<!-- <param name=\"jsonrpc-allowed-event-channels\" value=\"demo,conference,presence\"/> -->\n<param name=\"allow-empty-password\" value=\"false\"/>\n</params>\n<variables>\n<variable name=\"record_stereo\" value=\"true\"/>\n<variable name=\"default_areacode\" value=\"\$\${default_areacode}\"/>\n<variable name=\"language\" value=\"zh\"/>\n<variable name=\"default_language\" value=\"zh\"/>\n<variable name=\"transfer_fallback_extension\" value=\"operator\"/>\n</variables>\n<groups>\n<group name=\"default\">\n<users>";
			$result = $mysqli->query("select `user_name`,`user_id`,`password`,`group_id`,`reverse_user`, `reverse_pwd`,`dial_str`,`user_context`,`gateway`,`variables`,`cidr` from fs_users where `domain_id` = '$row[domain_id]' and `enabled`=1 order by group_id");
			$groups = array();
			$usrstr = "\n"; 
			while (($row0 = $result->fetch_array())!==false) {
				if (!$row0) break;
				$usrstr .= "<user id=\"$row0[user_id]\"";
				if ($row0['cidr'])
					$usrstr .="  cidr=\"$row0[cidr]\">\n";
				else
					$usrstr .= ">\n";
				$usrstr .= "<params>\n";
				$usrstr .= "<param name=\"password\" value=\"$row0[password]\"/>\n"; //	<param name="a1-hash" value="538db5a1dcf95cd9df62bf2ff0466c4b"/>  // ==  md5(username:domain:password)
				$usrstr .= "<param name=\"vm-password\" value=\"$row0[password]\"/>\n";
				if  ($row0['dial_str'])
					$usrstr .= "<param name=\"dial-string\" value=\"$row0[dial_str]\"/>\n";
				if ($row0['reverse_user'])
					$usrstr .= "<param name=\"reverse-auth-user\" value=\"$row0[reverse_user]\" />\n<param name=\"reverse-auth-pass\" value=\"$row0[reverse_pwd]\" />";
				$usrstr .= "</params>\n<variables>\n";

				if ($row0['variables']){
					$temp = explode("\n", $row0['variables']);
					foreach ($temp as $one){
						$var = explode("===", trim($one));
						if (isset($var[1]))
							$usrstr .= "<variable name=\"$var[0]\" value=\"$var[1]\"/>\n";
						else 
							$usrstr .= "<variable name=\"$var[0]\"/>\n";
					}
				}else{
					$usrstr .= "<variable name=\"toll_allow\" value=\"domestic,international,local\"/>\n";
					$usrstr .= "<variable name=\"accountcode\" value=\"$row0[user_id]\"/>\n";
					$usrstr .= "<variable name=\"effective_caller_id_name\" value=\" $row0[user_name] \"/>\n";
					$usrstr .= "<variable name=\"effective_caller_id_number\" value=\"$row0[user_id]\"/>\n";
					$usrstr .= "<variable name=\"outbound_caller_id_name\" value=\"\$\${outbound_caller_name}\"/>\n";
					$usrstr .= "<variable name=\"outbound_caller_id_number\" value=\"\$\${outbound_caller_id}\"/>\n";
				}
				if ($row0['user_context'])
					$usrstr .= "<variable name=\"user_context\" value=\"$row0[user_context]\"/>\n";
				else
					$usrstr .= "<variable name=\"user_context\" value=\"$row[domain_id]\"/>\n";
				$usrstr .= "<variable name=\"callgroup\" value=\"$row[domain_id]\"/>\n"; //把代答组设置为域ID	，代答组的人可以代答呼叫；
				if ($row0['gateway']){
					$usrstr .= "<variable name=\"register-gateway\" value=\"$row0[gateway]\"/>\n";
					$usrstr .= "</variables>\n";
					$lab = array("gatewayname","realm", "username","password","register","from-user","from-domain","regitster-proxy","outbound-proxy","expire-seconds","caller-id-in-from","extension","proxy","register-transport","retry-seconds","contact-params","ping","addon","variables");
					$gws = explode(",",$row0['gateway']);
					if ($gws){
						$usrstr .="<gateways>\n";
						foreach ($gws as $one){
							if (!isset($gwlist[$one]))
								continue;
								$i = 0;
								foreach ($lab as $key){
									if  ($i==0)
										$usrstr .=" <gateway name=\"{$gwlist[$one]['gatewayname']}\">\n";
										elseif($i<5)
										$usrstr .= " <param name=\"$key\" value=\"" . $gwlist[$one][$key] . "\"/>\n";
										elseif(!empty($gwlist[$one][$key]))
										if ($key=='variables' || $key=='addon' )
											$usrstr .= "{$gwlist[$one][$key]}\n";
											else
												$usrstr .= " <param name=\"$key\" value=\"" . $gwlist[$one][$key] . "\"/>\n";
												$i++;
								}
								$usrstr .=	" </gateway>\n";
						}
						$usrstr .="</gateways>\n";
					}
				}else 
					$usrstr .= "</variables>\n";
				$usrstr .="</user>\n";
				//将用户加入定义的组
				if ($row0['group_id']){
					$g = explode(",", $row0['group_id']);
					foreach ($g as $one){
						if (isset($groups[$one]))
							$groups[$one] .= "<user id=\"$row0[user_id]\"  type=\"pointer\"/>\n"; 
						else 
							$groups[$one] = "<user id=\"$row0[user_id]\"  type=\"pointer\"/>\n"; 
					}
				}
			}
			$xml .= "$usrstr</users>\n</group>\n";
			$result = $mysqli->query("select `group_id`,`calltype`,`calltimeout` from fs_groups where `domain_id` = '$row[domain_id]' and `enabled`=1");
			while (($row1 = $result->fetch_array())!==false) {
				if (!$row1) break;
				$xml .= "<group name=\"$row1[group_id]\">\n";
				if (isset($groups[$row1['group_id']]))
					$xml .= $groups[$row1['group_id']];
				$context .= "\n<extension name=\"Group $row1[group_id]\">\n<condition field=\"destination_number\" expression=\"^$row[group_prefix]$row1[group_id]$\">\n<action application=\"set\" data=\"hangup_after_bridge=true\"/>\n<action application=\"set\" data=\"continue_on_fail=true\"/>\n<action application=\"set\" data=\"originate_continue_on_timeout=true\"/>\n<action application=\"set\" data=\"call_timeout=$row1[calltimeout]\"/>\n<action application=\"bridge\" data=\"\${group_call($row1[group_id]@\${domain_name}$row1[calltype])}\"/>\n<action application=\"transfer\" data=\"$row1[group_id] XML default\"/>\n<action application=\"hangup\"/>\n</condition>\n</extension>\n";
				$xml .= "</group>\n";
			}
			$xml .=	"</groups>\n</domain>\n</include>";
			$context .='<extension name="operator">
<condition field="destination_number" expression="^(operator|0)$">
<action application="set" data="transfer_ringback=$${hold_music}"/>
<action application="transfer" data="1000 XML features"/>
</condition>
</extension>
<extension name="vmain">
<condition field="destination_number" expression="^vmain$|^\*98$">
<action application="answer"/>
<action application="sleep" data="1000"/>
<action application="voicemail" data="check default ${domain_name}"/>
</condition>
</extension>
<extension name="enum">
<condition field="${module_exists(mod_enum)}" expression="true"/>
<condition field="destination_number" expression="^(.*)$">
<action application="transfer" data="$1 enum"/>
</condition>
</extension>
</context>
</include>';
			$result = @file_put_contents($file_dir, $xml);
			unset($xml);
			if ($result){
				@file_put_contents($file_dia, $context);
				if (!is_dir($file_diadir))
					mkdir($file_diadir);	
				$info = new detect_switch();
				$info->run("reloadxml","",0);
				die(" $name 域已被添加并更新状态！");
			}else
				die("$name 域数据添加失败！");
		}
	}
	die("信息不完整，非法提交操作！");
}
//删除域记录
if (!empty($_POST['del'])){
	$id = intval($_POST['did']);
	$result = $mysqli->query("select `domain_id` from fs_domains where id = $id and `enabled`=0");
	$row = $result->fetch_array();
	if (!empty($row[0])){
		$mysqli->query("delete from fs_domains where id = $id limit 1");
		$mysqli->query("update fs_gateways set domain_id='',domain_user='' where domain_id = '$row[0]'");
		$mysqli->query("update fs_groups set `domain_id`='' where `domain_id` = '$row[0]' ");
		$mysqli->query("update fs_users set `domain_id`='',`group_id` = '' where `domain_id` = '$row[0]' ");
		die("id $id 操作完毕");
	}
	die("要删除的域，必须已被禁用！");
}
//设置启用或禁用
if (empty($_SESSION['POST_submit_once']) && !empty($_POST['sid'])){
	$id = intval($_POST['sid']);
	$to = !empty($_POST['en1'])? 1 : (!empty($_POST['en9'])? 9 : 0 );
	if ($to === 1){
		$_SESSION['POST_submit_once']=1;
		$mysqli->query("update fs_domains set `enabled` = 1 where id = $id limit 1");
		die("id $id 设置为可用完毕");
	}else{
		$_SESSION['POST_submit_once']=1;
		$mysqli->query("update fs_domains set `enabled` = 0 where id = $id limit 1");
		die("id $id 设置为禁用完毕");
	}
}
//----------------------显示----------域数据库 列表及信息管理----------------------
$_SESSION['POST_submit_once']=0;
echo "<html xmlns=http://www.w3.org/1999/xhtml><head><meta http-equiv=Content-Type content=\"text/html;charset=utf-8\">
<link rel=\"stylesheet\" type=\"text/css\" href=\"main.css\"/><script src=\"jquery.js\"></script><script>
function del(sid){var a = confirm(\"警告！！\\n删除操作同时也会清除本域全部的组及用户设置，不可撤销！！\\n你确认提交？\");if (a) { \$.post( \"FS_domains_cp.php\", { did: sid, del: \"1\" })
  .done(function( data ) { alert( \"删除成功！\" + data);$('#info'+sid).html('已经删除！'); });} }
function en0(sid){\$.post( \"FS_domains_cp.php\", { sid: sid, en0: \"1\" })
  .done(function( data ) { alert( \"禁用操作 \" + data);window.location.reload();});}
function en1(sid){\$.post( \"FS_domains_cp.php\", { sid: sid, en1: \"1\" })
  .done(function( data ) { alert( \"启用操作 \" + data);window.location.reload();});}
function en88(sid,lab){\$.post( \"FS_domains_cp.php\", { yid: sid, en0: \"88\", en1: lab})
  .done(function( data ) { alert( \"应用部署 \" + data);window.location.reload();});}
function en99(sid,lab){\$.post( \"FS_domains_cp.php\", { yid: sid, en0: \"99\",en1: lab})
  .done(function( data ) { alert( \"停用操作 \" + data);window.location.reload();});}
</script></head><body>";
$where = " where 1 ";
$showget = "<span class='smallred smallsize-font'> ";
if (!empty($_GET['gwname'])){
	$temp = $mysqli->real_escape_string($_GET['gwname']);
	$where .= " and `domain_name` like '%$temp%' ";
	$showget .=" 域名称包含 '$temp' ";
}

$count = 20;
$getstr = "";
$totle = $mysqli->query("select count(*) from fs_domains $where");
$row = $totle->fetch_array(MYSQLI_NUM);
$totle = $row[0];
$pages = ceil($totle/$count);
if (empty($_GET['p']))
	$p = 0;
else{
		$p = intval($_GET['p']);
		if ($p>$pages)
			$p = $pages;
			if ($p<0)
				$p = 0;
}
	$showget .= " （$totle 条，$pages 页）</span>";
	echo '<p class="pcenter" style="font-size:18pt;">域管理控制台 '.$showget.'  <a style="font-size:12pt;" href="?editDomain=0">【新建域】</a><a style="font-size:10pt;" href="index.php">返回主控</a></p><table class="tablegreen" width="90%" align="center"><th colspan=7><form method="get">域名称：<input id="gwname" name="gwname" value="" size=10>  <input type="submit" value="确认"> <a href="?">【看全部】</a>	</form></th>';
	$result = $mysqli->query("select * from fs_domains $where ORDER BY id DESC LIMIT ".($p*$count).",$count");
	while (($row = $result->fetch_array())!==false) {
		if (!$row)
			die('<tr><td colspan=7 align=center><span class="smallred smallsize-font"> *域新建后默认被禁用，需启用后方可应用！已应用的域可获取信息 或 停用；域设置后需启用，并需在拨号计划 或 用户管理中进行调用</span></td></tr></table><p class=\'red\'><a href="?list=1&p='.($p-1<0?0:$p-1).$getstr.'">前一页</a> '.($p==0?1:$p+1).'  <a href="?p='.($p+1>$pages?$pages:$p+1).$getstr.'">下一页</a> 
    跳转到：<input id="topage" name="togape" value="" size=4><input type="submit" value="确认" onclick="pa = document.getElementById(\'topage\').value-1;
    window.location.href=\'?p=\'+pa+\''.$getstr.'\';return false;"/></p></body></html>');
		else{
			if ($row['enabled']){
				$file_ = @$_SESSION['conf_dir']."/directory/".$row['domain_id'].".xml";
				if (is_file($file_)){
					$showalert= ' <span class="bggreen">已应用 </span>&nbsp; '.$row['id'].' &nbsp; <em class=\'red\'>'.$row['domain_name'].'</em>';
					$showtools=" <input type='button' onclick=\"this.value='连接中，请等待反馈...';en99($row[id],'$row[domain_id]')\" value='停用'/>";
				}else{
					$showalert= ' <span class="bgblue">已停用 </span>&nbsp; '.$row['id'].' &nbsp; <em class=\'red\'>'.$row['domain_name'].'</em>';
					$showtools="<input type='button' onclick=\"this.value='连接中，请等待反馈...';en88($row[id],'$row[domain_id]')\" value='部署应用'/> &nbsp;  <input type='button' onclick=\"en0($row[id])\" value='禁止'/>";
				}
			}else 
				$showalert= ' <span class="bgred">已禁止 </span>&nbsp; '.$row['id'].'  &nbsp; <em class=\'red\'>'.$row['domain_name'].'</em>';
			
			$totle = $mysqli->query("SELECT `enabled` ,COUNT(*) FROM fs_groups WHERE `domain_id` = '$row[domain_id]'  GROUP BY `enabled` order by `enabled` ");
			$dialplans = result_fetch_all($totle);
			unset($totle);
			$totle = array('0'=>0,'1'=>0);
			foreach ($dialplans as $one){
				if ($one[0]=='1') $totle['1']=$one[1];
				else $totle[$one[0]]=$one[1];
			}
			$showguser = "含组：可用<strong> $totle[1] </strong>   不可用<strong> $totle[0] </strong>  <a href='FS_groups_cp.php?dmid=$row[domain_id]'>&raquo;&nbsp;管理组</a>";
			
			$totle = $mysqli->query("SELECT `enabled` ,COUNT(*) FROM fs_users WHERE `domain_id` = '$row[domain_id]'  GROUP BY `enabled` order by `enabled` ");
			$dialplans = result_fetch_all($totle);
			unset($totle);
			$totle = array('0'=>0,'1'=>0);
			foreach ($dialplans as $one){
				if ($one[0]=='1') $totle['1']=$one[1];
				else $totle[$one[0]]=$one[1];
			}
			$showuser = "含用户：可用<strong> $totle[1] </strong>  不可用<strong> $totle[0] </strong>  <a href='FS_users_cp.php?dmid=$row[domain_id]'>&raquo;&nbsp;管理用户</a>";
			
			if ($row['parent_id'])
				$showu = " 上级域：<strong>".$dmlist[$row['parent_id']]."</strong>";
			else 
				$showu = "<span class=\"smallgray smallsize-font\">无上级域</span>";
			$options = "Level:<strong>".$row["level"]."</strong>";
			$bgcolor = fmod($row['id'],2)>0?"class='bg1'":"class='bg2'";
			echo "<tr $bgcolor><td>$showalert</td><td>域标识：<a href='FS_files_edit.php?domain=$row[domain_id]'><strong>$row[domain_id]</strong></a></td><td> $showu</td><td> $showguser</td><td> $showuser</td><td>$options</td><td><a href='?editDomain=$row[id]'>详情及修改...</a> <span id='info$row[id]' style='font-size:9pt;color:red;'>";
			if ($row['enabled']){
				echo $showtools;
			}else 
				echo " <button onclick=\"en1($row[id])\">启用</button> <button onclick=\"del($row[id])\">删除</button>";
			echo "</span></td></tr>";
		}
	}
$mysqli->close();
