<?php
 set_time_limit(600);
 session_start(); 
 date_default_timezone_set('Asia/Shanghai');
header("Content-type: text/html; charset=utf-8");

include 'Shoudian_db.php';
$file =__DIR__.'/.Config';
$conf = @unserialize(file_get_contents($file));
//----------------------显示---------------------------------
$_SESSION['POST_submit_once']=0;

echo "<html xmlns=http://www.w3.org/1999/xhtml><head><meta http-equiv=Content-Type content=\"text/html;charset=utf-8\">
<link rel=\"stylesheet\" type=\"text/css\" href=\"main.css\"/>
<style>
.act2{font-size:8pt;color:red;text-align:center;}
#win{position:absolute;top:50%;left:50%;width:400px;height:200px;background:#fff;border:4px solid #f90;margin:-102px 0 0 -202px;display:none;}
button{cursor:pointer;background:#f90;border:1px solid #303030;padding:3px;}
h2{font-size:14pt;height:18pt;text-align:right;background:#FC0;border-bottom:3px solid #f90;padding:0px;margin:0px;}
h2 span{color:#f90;cursor:pointer;background:#fff;border:1px solid #f90;padding:0 2px;}
</style><script type=\"text/javascript\" src=\"jquery.js\"></script><script type=\"text/javascript\">
function showcodeL(sid,iid){\$.post( \"FS_error_code.php\", { Q850_reason_label: sid }).done(function( data ) { $('#info'+iid).html(data);});}
function showcodeC(sid,iid){\$.post( \"FS_error_code.php\", { Q850_reason_code: sid }).done(function( data ) { $('#info'+iid).html(data);});}
function showcodeS(sid,iid){\$.post( \"FS_error_code.php\", { SIP_reason_code: sid }).done(function( data ) { $('#info'+iid).html(data);});}
</script></head><body><div id=\"win\"><h2 id=\"tt\"><span id=\"close\" onclick=\"$('#win').css('display','none')\" style=\"pading:5px;\"> × </span></h2>
    <p id=\"title\" class=\"act2\"></p>
<audio id=\"player\" controls=\"controls\" src=\"\">你的浏览器不支持audio标签。</audio></div>";
$count = 5;
$getstr = "";
$showget = "<span class='smallred smallsize-font'> ";
$where = 'where 1 ';
$date = time();
$startdate = date("Y-m-d H:i:s",$date-86400);
$enddate = date("Y-m-d H:i:s",$date);
if (!empty($_GET['viewrelation'])){
	$ids = explode(",",$_GET['viewrelation']);
	$result = $mysqli->query("select * from fs_xml_cdr where uuid in ( '$ids[0]','$ids[1]')");
	$totle = count($ids);
	$p = 0;
	$pages = 1;
	$showget .= "（ $totle 条，$pages 页）</span>";
}else{
	if (!empty($_GET['startdate']) && !empty($_GET['enddate'])){
		$date = strtotime($_GET['startdate']);
		if ($date)
			$date1 = strtotime($_GET['enddate']);
			if($date && $date1){
				$where .= "and `start_stamp` >= '$_GET[startdate]' and `start_stamp` <= '$_GET[enddate]'";
				$getstr .="&startdate=$_GET[startdate]&enddate=$_GET[enddate]";
				$showget .=" <b>$_GET[startdate] </b> 到  <b>$_GET[enddate]</b> ";
				$startdate = $_GET['startdate'];
				$enddate = $_GET['enddate'];
			}
	}
	if (!empty($_GET['limit_ans'])){
		$where .= "and `answer_epoch` > 0 ";
		$getstr.= "&limit_ans=1";
		$showget .=" 仅显示已应答话单 ";
		$limit_ans_c = "checked=\"checked\" ";
	}else $limit_ans_c = "";
	if (!empty($_GET['phone']) && is_numeric ($_GET['phone'])){
		$phone = $_GET['phone'];
		$where .= "and `destination_number` like '%$phone%' ";
		$getstr.= "&phone=$phone";
		$showget .=" 被叫号<b>【 $phone 】</b>";
	}
	if (!empty($_GET['phone0']) && is_numeric ($_GET['phone0'])){
		$phone = $_GET['phone0'];
		$where .= "and `caller_id_number` like '%$phone%' ";
		$getstr.= "&phone0=$phone";
		$showget .=" 主叫id<b>【 $phone 】</b>";
	}
	if (!empty($_GET['domain'])){
		$domain = $_GET['domain'];
		$where .= "and `domain_name` like '%$domain%' ";
		$getstr.= "&domain=$domain";
		$showget .=" 域id<b>【 $domain 】</b>";
	}
	$totle = $mysqli->query("select count(*) from fs_xml_cdr $where");
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
		$showget .= "（ $totle 条，$pages 页）</span>";
	$result = $mysqli->query("select * from fs_xml_cdr $where ORDER BY start_stamp DESC LIMIT ".($p*$count).",$count");
}
	echo '<p class="pcenter" style="font-size:18pt;">CDR 通话详单  '.$showget.' <a style="font-size:10pt;" href="index.php">&raquo;&nbsp;返回主控</a></p><table class="tablegreen" width="90%" align="center"><th colspan=5><form method="get"><p style="text-align:center;">域：<input id="domain" name="domain" value="" size=10> 主叫id：<input id="phone0" name="phone0" value="" size=10> 被叫id：<label><input id="phone" name="phone"  value=""> 开始：<input id="startdate" name="startdate" value="'.$startdate.'" size="15"> 结束：<input id="enddate" name="enddate" value="'.$enddate.'" size="15"> <label><input id="limit_ans" name="limit_ans" value="1" type="checkbox" '.$limit_ans_c.' >仅显示已应答</label> &nbsp;  <input type="submit" value="确认"/></th>';
	$i=0;
	if (!isset($conf['CDR_file']))
		$filename = '@start_stamp@_@destination_number@_@caller_id_number@.wav';
	else 
		$filename = $conf['CDR_file'];

	while (($row = $result->fetch_array())!==false) {
		if (!$row)
			die('</table><p class=\'red\'><a href="?list=1&p='.($p-1<0?0:$p-1).$getstr.'">前一页</a> '.($p==0?1:$p+1).'  <a href="?p='.($p+1>$pages?$pages:$p+1).$getstr.'">下一页</a> 
    跳转到：<input id="topage" name="togape" value="" size=4><input type="submit" value="确认" onclick="pa = document.getElementById(\'topage\').value-1;
    window.location.href=\'?p=\'+pa+\''.$getstr.'\';return false;"/></p></body></html>');
		else{
			if ($row['direction']=='inbound')
				$shownumber = "<span class=\"bggreen\">来话inbound</span> $row[caller_id_number] ($row[caller_id_name]) -><span class=\"bold14\">$row[destination_number]</span>";
			else
				$shownumber= "<span class=\"bgblue\">去话outbund</span> $row[caller_id_number] ($row[caller_id_name]) -><span class=\"bold14\">$row[destination_number]</span>";
			if ($row['read_codec'])
				$showcode = " 读编码：<b>'$row[read_codec] $row[read_rate]'</b><br/> 写编码：<b> '$row[write_codec] $row[write_rate]'</b>";
			else 
				$showcode = "<span class='smallgray smallsize-font'>无编码信息</span>";
			if ($row['accountcode'])
				$accountcode = " 账号：<b> '$row[accountcode]'</b> ";
			else $accountcode = "";
			if ($row['domain_name'])
				$domain_name = "<span class=\"smallorange\">@$row[domain_name]</span> ";
			else $domain_name = "";
			if ($row['extension_uuid'])
				$extension_uuid = " ext：<b>'$row[extension_uuid]'</b> ";
			else $extension_uuid = "";
			if ($row['context'])
				$context = " context：<b>'$row[context]'</b> ";
			else $context = "";
			if ($row['sip_gateway'])
				$sip_gateway = " sip_gateway：<b>'$row[sip_gateway]'</b> ";
			else $sip_gateway = "";
			if ($row['last_app'])
				$last_app = " last_app：<b>'$row[last_app] $row[last_arg]'</b> ";
			else $last_app = "";
			$showuser =" $accountcode $context $extension_uuid $sip_gateway $last_app ";
			$filestr = "";
			if (!empty($row['billsec'])){
				$answertime =   "应答于 ".$row['answer_stamp']." 通话 $row[billsec] 秒  ($row[billmsec] 毫秒)";
				$pathstr = date("Ymd", $row['start_epoch']);
				preg_match_all('|@([^@]*)@|',$filename,$nameparts);
				if (is_array($nameparts[1]))
					foreach ($nameparts[1] as $one){
						if (in_array($one, ['start_stamp','end_stamp','answer_stamp'])){
							$namefind[] = "@$one@";
							$namerepl[] = str_replace(array(" ",":"),"-", $row[$one]);
						}else{
							$namefind[] = "@$one@";
							$namerepl[] = $row[$one];
						}
				}
				$filestr0 = str_replace($namefind,$namerepl,$filename);
				$filestr = @$_SESSION['recordings_dir'].'/'.$pathstr.'/'.$filestr0;
				$fileplay = '/files/'.$pathstr.'/'.$filestr0;
				if (file_exists($filestr))
					$filestr = "<button type='button' onclick='$(\"#win\").css(\"display\",\"block\");$(\"#title\").html(\" <b>主叫：</b>$row[orig_caller_number] <b>被叫：</b>$row[orig_callee_number]\");$(\"#player\").attr(\"src\",\"$fileplay\");'> 【播放语音】 </button>";
				else
					$filestr = $fileplay = "";
			}else
				$answertime = "";
			$showtime = "$row[start_stamp] 到 $row[end_stamp] <br/>$answertime 呼叫 $row[duration] 秒";
			if (!empty(($row["xml"]))){
				$dtailinfo = "<span id='cdr-$row[uuid]' style='display:none;'>".urldecode(htmlentities(@gzuncompress ($row["xml"]),ENT_QUOTES))."</span>";
			}else 
				$dtailinfo = "<span class='smallgray smallsize-font'>无信息</span>";
			$showip = "<span class='smallblack smallsize-font'>$showuser<br/><b>IP: </b>$row[ip]<br/><span onclick='\$(\"#cdr-$row[uuid]\").toggle()' style=\"cursor:pointer;\"> 【** 点击展开关闭 话单详情 **】-> </span>$dtailinfo</span>";
			$bgcolor = fmod($i,2)>0?"class='bg1'":"class='bg2'";
			$i++;
			$showend = "<span class=\"smallgray smallsize-font\" style=\"cursor:pointer;\" onclick=\"showcodeL('$row[hangup_cause]',$i)\">$row[hangup_cause]</span> &nbsp; <span class=\"orange\" style=\"cursor:pointer;\"  onclick=\"showcodeC('$row[hangup_cause_q850]',$i)\">($row[hangup_cause_q850])</span> &nbsp; $row[sip_hangup_disposition]";
			echo "<tr class=bg1><td>$shownumber $domain_name</td><td align=center>$showtime</td><td>$showcode</td><td>$showend  [$row[leg]] <br/>$filestr <span id='info$i' class=\"smallgray smallsize-font\"></span></td><td>";
			if ($row['bridge_uuid'])
				echo (isset($_GET['viewrelation'])?"<a href='?'>【查看全部】</a>":"<a href='?viewrelation=$row[uuid],$row[bridge_uuid]'>【关联查看】</a>");
			else 
				echo "";
			echo "</td></tr><tr class=bg2><td colspan=5>".nl2br($showip)."</td></tr>";
		}
	}
$mysqli->close();
