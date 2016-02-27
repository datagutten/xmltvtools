<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Untitled Document</title>
<style>
td {
    vertical-align: top;
	min-width: 90px;
}
</style>
</head>
<body>
<?Php
setlocale(LC_ALL, "nb_NO.UTF8");

require_once 'tvguide.class.php';
$tvguide=new tvguide;
$timeformat='Hi';


if (!isset($_GET['start']))
	$time=strtotime("monday");
else
	$time=strtotime($_GET['start']);
if(!isset($_GET['numdays']))
	$numdays=7;
else
	$numdays=$_GET['numdays'];

if (!isset($_GET['channel']) || empty($_GET['channel']))
{
	echo 'Select a channel:<br>';
	foreach($tvguide->channels as $channel)
	{
		echo "<li><a href=\"?channel=$channel\">$channel</a></li>\n";
	}
	die();
}
else
	$channel=$_GET['channel'].'_';

echo '<table border="1">';
for ($i=1; $i<=$numdays; $i++) //Lag en rad for hver dag
{

	$date=date('Y-m-d',$time);
	//$filename="../../tv/$channel$date.xml";

	if (!$programs=$tvguide->getprograms($_GET['channel'],$time))
	{
		$errors.=$tvguide->error."<br />\n";
		$time=strtotime('+1 day',$time);
		continue;
	}
	echo "<tr>\n";
	echo "\t".'<td width="86">'.date('l',$time)."<br />".date('Y-m-d',$time)."</td>\n"; //Show date
	$key=0;
	foreach($programs as $programme)
	{
		if(isset($_GET['program']) && strpos($programme->title,$_GET['program'])===false)
			continue;
		
		echo "\t<td>";
		$attributes=$programme->attributes();
		echo date($timeformat,strtotime($attributes->start));
		if(!empty($attributes->stop))
			echo "-".date($timeformat,strtotime($attributes->stop));
		echo "<hr>";
		echo $programme->title."<br />\n"; 
		echo $tvguide->seasonepisode($programme);
		echo "</td>\n";
	}
	
	echo "</tr>\n";
	$time=strtotime('+1 day',$time);

}

echo "</table>\n";
echo $errors;
?>
</body>
</html>
