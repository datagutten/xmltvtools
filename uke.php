<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Untitled Document</title>
</head>
<body>
<?Php
setlocale(LC_ALL, "nb_NO.UTF8");

require_once 'tvguide.class.php';
$tvguide=new tvguide;

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

	if (!($data=$tvguide->loadxmlfile($_GET['channel'],$time)) || !($xml=$tvguide->xmltoarray($data)))
	{
		$time=strtotime('+1 day',$time);
		continue;
	}
	
	echo "<tr>\n";
	
	
	echo "\t".'<td rowspan="2" width="86">'.date('l d-m-y',$time)."</td>\n"; //Vis dato

	if(isset($_GET['program']))
	{
		foreach ($xml['programme'] as $key=>$program)
		{
			if(strpos($program['title'],$_GET['program'])===false)
				unset($xml['programme'][$key]);
		}
	}
	foreach($xml['programme'] as $program)
	{
		echo "\t<td>".date('Hi',strtotime($program['@attributes']['start']));
		if(isset($program['@attributes']['stop']))
			echo '-'.date('Hi',strtotime($program['@attributes']['stop']))."</td>\n"; //Vis tidene
	}
	echo "</tr>\n";
	echo "<tr>\n";
	foreach($xml['programme'] as $program)
	{
		echo "\t<td>{$program['title']}";

		echo "</td>\n";
	}
	
	echo "</tr>\n";
	unset($xml);
	$time=strtotime('+1 day',$time);

}

echo "</table>\n";
echo $tvguide->error;
?>
</body>
</html>
