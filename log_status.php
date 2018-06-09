<?php

$db_host = "localhost";
$db_user = "root";
$db_passwd = "";
$db_dbname = "checksite";

$mysqli = new mysqli($db_host, $db_user, $db_passwd, $db_dbname);
if(mysqli_connect_error()){
	echo mysqli_connect_error();
}

if ($_SERVER['argc']!=9)  {
	echo  "log_status.php hostname ipv4 aaaa ipv6 httpsv4 httpsv6 http2v4 http2v6\n";
	exit(0);
}

function update_last($hostname,$ipv4,$aaaa,$ipv6,$httpsv4,$httpsv6,$http2v4,$http2v6)
{
	global $mysqli;
	echo "update_last\n";
	$q="replace into status_last values(?, now(), ?,?,?,?,?,?,?)";
	$stmt=$mysqli->prepare($q);
	$stmt->bind_param("siiiiiii",$hostname,$ipv4,$aaaa,$ipv6,$httpsv4,$httpsv6,$http2v4,$http2v6);
	$stmt->execute();
	$stmt->close();
}

function insert_log($hostname,$ipv4,$aaaa,$ipv6,$httpsv4,$httpsv6,$http2v4,$http2v6)
{
	global $mysqli;
	echo "insert_log\n";
	$q="insert into status_log values(?, now(), ?,?,?,?,?,?,?)";
	$stmt=$mysqli->prepare($q);
	$stmt->bind_param("siiiiiii",$hostname,$ipv4,$aaaa,$ipv6,$httpsv4,$httpsv6,$http2v4,$http2v6);
	$stmt->execute();
	$stmt->close();
}

function update_allok($hostname)
{
	global $mysqli;
	echo "update_allok\n";
	$q="select count(*) from allok_first where hostname=?";
	$stmt=$mysqli->prepare($q);
	$stmt->bind_param("s",$hostname);
	$stmt->execute();
	$stmt->bind_result($cnt);
	$stmt->store_result();
	$stmt->fetch();
	$stmt->close();
	if($cnt==1) 
		return;

	$q="insert into allok_first values(?, now())";
	$stmt=$mysqli->prepare($q);
	$stmt->bind_param("s",$hostname);
	$stmt->execute();
	$stmt->close();
}
	
$hostname=$_SERVER['argv'][1];
$ipv4=$_SERVER['argv'][2];
$aaaa=$_SERVER['argv'][3];
$ipv6=$_SERVER['argv'][4];
$httpsv4=$_SERVER['argv'][5];
$httpsv6=$_SERVER['argv'][6];
$http2v4=$_SERVER['argv'][7];
$http2v6=$_SERVER['argv'][8];

if($aaaa+$ipv6+$httpsv4+$httpsv6+$http2v4+$http2v6==6)
	update_allok($hostname);

// 检查status_last 是否有记录
$q="select ipv4, aaaa, ipv6, httpsv4, httpsv6, http2v4, http2v6 from status_last where hostname=?";
$stmt=$mysqli->prepare($q);
$stmt->bind_param("s",$hostname);
$stmt->execute();
$stmt->bind_result($oldipv4, $oldaaaa, $oldipv6, $oldhttpsv4, $oldhttpsv6, $oldhttp2v4, $oldhttp2v6);
$stmt->store_result();
if(!$stmt->fetch()) {	// 第一次记录
	$stmt->close();

	update_last($hostname,$ipv4,$aaaa,$ipv6,$httpsv4,$httpsv6,$http2v4,$http2v6);

	insert_log($hostname,$ipv4,$aaaa,$ipv6,$httpsv4,$httpsv6,$http2v4,$http2v6);

	exit(0);
}
$stmt->close();

// 之前有过记录
update_last($hostname,$ipv4,$aaaa,$ipv6,$httpsv4,$httpsv6,$http2v4,$http2v6);

if( ($ipv4!=$oldipv4) || ($aaaa!=$oldaaaa) || ($ipv6!=$oldipv6) 
 || ($httpsv4!=$oldhttpsv4) || ($httpsv6!=$oldhttpsv6) 
 || ($http2v4!=$oldhttp2v4) || ($http2v6!=$oldhttp2v6) ) 
	insert_log($hostname,$ipv4,$aaaa,$ipv6,$httpsv4,$httpsv6,$http2v4,$http2v6);

?>
