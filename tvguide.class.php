<?Php
require 'class_filepath.php';
class tvguide extends filepath
{
	public $xmltvpath;
	public $xmltv_cache;
	public $xmltv_subfolder_main;
	public $xmltv_subfolder_alt;
	public $error;
	public $debug=false;
	public $channels;
	public $linebreak="<br />\n";
	public function __construct()
	{
		ini_set('display_errors',1);
		error_reporting(E_ALL);
		require 'config_xmltv.php';
		if(!isset($xmltv_subfolder_main))
			die("Main subfolder not specified ($xmltv_subfolder_main)");
		$this->xmltv_subfolder_main=$xmltv_subfolder_main;
		if(isset($xmltv_subfolder_alt))
			$this->xmltv_subfolder_alt=$xmltv_subfolder_alt; //Subfolder for alternative source
		if(substr($xmltvpath,-1,1)!='/')
			$xmltvpath.='/';
		parent::__construct($xmltvpath);
		$this->xmltvpath=$xmltvpath;
		if(isset($xmltv_cache))
		{
			if(substr($xmltv_cache,-1,1)!='/')
				$xmltv_cache.='/';
			$this->xmltv_cache=$xmltv_cache;
		}
		$this->channels=explode("\n",trim(file_get_contents($this->xmltvpath.'channels.txt')));
	}
	 public function selectchannel($channelstring)
	 {
		require 'channellist.php'; //Load channel name to xmltv id mappings
		if(isset($channellist[$channelstring]))
			return $channellist[$channelstring];
		else
		{
			$this->error="Unkown channel: $channelstring";
			return false;
		}
	 }
	 public function parsefilename($input)
	 {
		if(!preg_match('^([0-9]{8} [0-9]{4}) - (.*) - (.*)\.ts^U',$input,$result))		
		{
			$this->error='Could not parse file name';
			return false;
		}
		else
			return array('datetime'=>$result[1],'channel'=>$result[2]);

	 }
	 public function filecheck($file)
	 {
		if(strpos($file,'http://')===false)
			return file_exists($file);
		else
		{ //http://stackoverflow.com/questions/10444059/file-exists-returns-false-even-if-file-exist-remote-url
			$file_headers = get_headers($file);
			if($file_headers[0] == 'HTTP/1.1 404 Not Found')
			{
				return false;	
			}
			elseif($file_headers[0] == "HTTP/1.1 200 OK")
				return true;
			elseif($this->debug)
			{
				print_r($file_headers);
				return false;
			}
			else
				return false;
		}
	 }
	public function getchannel($channelstring,$timestamp)
	{
		$channelid=$this->selectchannel($channelstring);
		if($channelid===false)
			return false;	
		else
			return $this->loadxmlfile($channelid,$timestamp);
	}
	public function loadxmlfile($channelid,$timestamp,$returntype='object',$forcesubfolder=false) //Get the xml file for the specified channel and time
	{
		//Return can be object, array or string
		$ymd=date('Y-m-d',$timestamp);
		$basename="$channelid/{$channelid}_$ymd.xml";

		$files['main']=$this->fullpath($channelid,$this->xmltv_subfolder_main,$timestamp,'xml');
		if(isset($this->xmltv_subfolder_alt))
			$files['alt']=$this->fullpath($channelid,$this->xmltv_subfolder_alt,$timestamp,'xml');
		$files['cache']=str_replace($this->xmltvpath,$this->xmltv_cache,$files['main']);

		if($forcesubfolder!==false)
		{
			$checkfiles=array($this->fullpath($channelid,$forcesubfolder,$timestamp,'xml'));
		}
		else
			$checkfiles=array($files['cache'],$files['main'],$files['alt']);
		
		foreach($checkfiles as $path)
		{
			if($this->filecheck($path))
			{
				$xmlstring=file_get_contents($path); //Load the xml file
				if($channelid=='tvnorge.no') //TV Norge need some string manipulation
					$xmlstring=str_replace('&lt;br/&gt;&lt;br/&gt;','',$xmlstring);
				$xml=simplexml_load_string(str_replace('&','&amp;',$xmlstring)); //Parse the file and create a simplexml object
				if(!isset($xml->programme))
				{
					$temperror="Invalid XML file for $channelid $ymd ($path)<br>\n";
					unset($xml);
					continue; //Invalid file, try next
				}
				else
				{
					if($this->debug)
						$this->error.="Successfully loaded $path".$this->linebreak;
					break; //Valid file found, no need to continue
				}
			}
			else
			{
				$temperror="No XML file found for $channelid $ymd".$this->linebreak;
				continue; //No file found, try next
			}
		}
		if(!isset($xml) || $xml===false) //If we are here without xml data, no valid file was found
		{
			$this->error.=$temperror;
			return false;
		}

		if(strpos($this->xmltvpath,'http://')!==false && isset($files['cache'])) //If the file was fetched from a remote location, it should be cached
		{
			if(!file_exists($cachedir=dirname($files['cache'])))
				mkdir($cachedir,0777,true);
			file_put_contents($files['cache'],$xmlstring);
		}

		if($returntype=='string')
			return $xmlstring; //Return the raw file
		elseif($returntype=='object')
			return $xml; //Return the simplexml object
		elseif($returntype=='array')
			return json_decode(json_encode($xml),true); //Convert the xml object to array
	}

	public function xmltoarray($xml)
	{
		return @json_decode(@json_encode($xml),true); //Gjør om til array
	}
	public function getprograms($channel,$timestamp) //Combine data for current day and previous day to get all programs for the current day
	{
		$xml_today=$this->loadxmlfile($channel,$timestamp);
		$xml_yesterday=$this->loadxmlfile($channel,$timestamp-86400);
		$date_request=date('Ymd',$timestamp);
		foreach(array($xml_yesterday,$xml_today) as $day)
		{
			foreach($day as $program)
			{
				if($date_request!=substr($program->attributes()->start,0,8)) //Wrong date
					continue;
				$programs[]=$program;
			}
		}
		return $programs;
	}	
	public function recordinginfo($filename) //Find information about a recorded file
	{
		//list($datetime,$channelstring) = 
		$info=$this->parsefilename($filename);
		$timestamp=strtotime($info['datetime']);

		$channelstring=$info['channel'];
		if(!$channelid=$this->selectchannel($channelstring))
		{
			$this->error='Invalid channel: $channelstring';
			return false;
		}

		if(!$xml=$this->loadxmlfile($channelid,$timestamp))
			return false;
		else
		{			
			$xml=(object)array('programme'=>$this->getprograms($channelid,$timestamp));

			$xmlprogram=$this->findprogram($timestamp,$xml,array(0,5*60,10*60,15*60,60)); //Se etter programmer som starter om kort tid
			if($xmlprogram===false)
		   		$xmlprogram=$this->findprogram($timestamp,$xml,'now'); //Se hvilket program som gikk på opptakstidspunktet

			return $xmlprogram;
			
		}
	}
	public function findprogram($time,$xml,$offsets=array(300)) //Find a program from a tv-listing
	{
		if(!is_object($xml))
		{
			$this->error="Ugyldig xml".$this->linebreak;	
			return false;
		}
		//Opptak starter 5 min før programmet
		//Sjekk om start-5 er samme som opptak
		if($offsets==='now')
			$now=$time;
		else
			$now=time();

		//$now=strtotime('2013-12-26 10:59');
		foreach($xml->programme as $program)
		{
			$start=strtotime($program->attributes()->start);

			//var_dump($offsets);
			if(is_array($offsets))
			{
				foreach($offsets as $offset)
				{
					//echo $start-$offset;
					//echo "==$time\n";
					if($start-$offset==$time)
						return $program;
				}
			}
			elseif($offsets==='now')
			{
				$diff=$now-$start;
				if($this->debug)
				{
					echo "$now-$start=$diff\n";
					echo date('H:i',$now).'-'.date('H:i',$start)."\n";
				}

				if($diff<0)
				{
					if(isset($prevprogram))
						return $prevprogram;
					else
					{
						$this->error.="Matcher første program".$this->linebreak;
						return false;
					}
				}


			}
			
			$prevprogram=$program;
		}
		if(!isset($prevprogram) && $time<$start)
		{
			$this->error.="Nothing on air at given time".$this->linebreak;
			return false;
		}
		$this->error.="No program found".$this->linebreak;
		return false;
	}
	public function seasonepisode($program,$string=true)
	{
		foreach($program->{'episode-num'} as $num)
		if(preg_match('^([0-9]+) \. ([0-9]+)/([0-9]+)^',$num,$matches) || preg_match('^([0-9]+) \. ([0-9]+)^',$num,$matches) || preg_match('^([0-9]+)\.([0-9]+)/([0-9]+)^',$num,$matches))
		{
			if($string)
			{
				$season=str_pad($matches[1]+1,2,'0',STR_PAD_LEFT);
				$episode=str_pad($matches[2]+1,2,'0',STR_PAD_LEFT);
				return "S{$season}E$episode";
			}
			else
				return array('season'=>$matches[1]+1,'episode'=>$matches[2]+1);
		}
		elseif(preg_match('^\. ([0-9]+)/([0-9]+) \.^',$num,$matches)) //One shot series
		{
			if($string)
				return "EP".str_pad($matches[1]+1,2,'0',STR_PAD_LEFT);
			else
				return array('season'=>0,'episode'=>$matches[1]+1);
		}
		return false;

	}
	public function eitparser($eitfile,$mode='title')
	{
		if(!file_exists($eitfile))
			return false;
		$eitfile=file_get_contents($eitfile);
		if(($pos=strpos($eitfile,''))===false)
			$pos=strpos($eitfile,'');
		$info['title']=utf8_encode(trim(substr($eitfile,$pos)));
		//die($title);

			if(preg_match('^\(([0-9]+)/s([0-9]+)\)^',$eitfile,$seasonepisode))
			{
				$info['seasonepisode']['season']=$seasonepisode[2];
				$info['seasonepisode']['episode']=$seasonepisode[1];
			}
		if($mode=='array')
			return $info;
		else
			return $info['title'];
	}
	

}
