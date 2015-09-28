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

		if($forcesubfolder!==false)
			$files=array($this->fullpath($channelid,$forcesubfolder,$timestamp,'xml'));
		else
		{
			$files['main']=$this->fullpath($channelid,$this->xmltv_subfolder_main,$timestamp,'xml');
			if(isset($this->xmltv_subfolder_alt))
				$files['alt']=$this->fullpath($channelid,$this->xmltv_subfolder_alt,$timestamp,'xml');
			$files['cache']=str_replace($this->xmltvpath,$this->xmltv_cache,$files['main']);
		}
		
		foreach($files as $path)
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
					break; //Valid file found, no need to continue
			}
			else
			{
				$temperror=sprintf('No XML file found for %s %s',$channelid,$ymd);
				continue; //No file found, try next
			}
		}
		if(!isset($xml) || $xml===false) //If we are here without xml data, no valid file was found. Return the error message
		{
			$this->error=$temperror;
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
		$xml_current_day=$this->loadxmlfile($channel,$timestamp);
		$xml_previous_day=$this->loadxmlfile($channel,$timestamp-86400);
		if($xml_current_day===false || $xml_previous_day===false)
			return false;
		$date_request=date('Ymd',$timestamp);
		foreach(array($xml_previous_day,$xml_current_day) as $day)
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
			return false;
		$programs_xml=$this->getprograms($channelid,$timestamp);

		if($programs_xml===false)
			return false;
		else
		   	return $this->findprogram($timestamp,$programs_xml,'nearest'); //Find the program start nearest to the search time			
	}
	//Get program running at the given time or the next starting program
	//$mode can be now (running program at search time), next (next starting program) or nearest (program start with lowest difference to search time)
	public function findprogram($search_time,$programs_xml,$mode='nearest')
	{
		if($programs_xml===false)
			return false;
		elseif(!is_array($programs_xml))
			throw new Exception('$programs_xml must be array');

		foreach($programs_xml as $key=>$program) //Loop through the programs
		{
			$program_start=strtotime($program->attributes()->start); //Get program start
			if($key==0 && $this->debug)
				echo sprintf("First program start: %s date: %s\n",(string)$program->attributes()->start,date('c',$program_start));

			$time_to_start[$key]=$program_start-$search_time; //How long is there until the program starts?
			$diff=$search_time-$program_start;

			if($this->debug)
				echo sprintf("Time to start: %s (%s seconds) Program starts: XML: %s date: %s Timestamp: %s\n",date('H:i',$time_to_start[$key]),$time_to_start[$key],$program->attributes()->start,date('H:i',$program_start),$program_start);

			if($key==0 && $time_to_start[$key]>0) //First program has not started
			{
				if($mode=='next' || $mode=='nearest')
					return $program;
				elseif($mode=='now')
				{
					$this->error='Nothing on air at given time';
					return false;
				}
			}

			if($mode=='next' && $time_to_start[$key]>=0) //Find first program which has not started
				return $program;
			elseif($mode=='now')
			{
				if($time_to_start[$key]>0) //Current program has not started, return the previous (running now)
					return $programs_xml[$key-1];
			}
			elseif($mode=='nearest' && $key>0) //Get the nearest start
			{
				$time_to_start_previous=$time_to_start[$key-1];
				$time_to_start_current=$time_to_start[$key];

				if($time_to_start_previous<0)
					$time_to_start_previous=-$time_to_start_previous;
				if($time_to_start_current<0)
					$time_to_start_current=-$time_to_start_current;
				if($this->debug)
					echo sprintf("%s<%s\n",$time_to_start_previous,$time_to_start_current);
				if($time_to_start_previous<$time_to_start_current) //Previous diff was lower
					return $programs_xml[$key-1];
				if(!isset($programs_xml[$key+1])) //If we are on the last program and haven't returned yet, return the current program
					return $program;
			}
		}
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
