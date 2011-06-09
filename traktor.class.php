<?php
/*----------------------------------------------------------------------
	
	WARNING WARNING WARNING WARNING: THIS CODE DOESN'T WORK, YET!! 
	SO, IF YOU WANT, PLEASE CONTRIBUTE BUT DON'T COMPLAIN!!!
	
	traktor.class.php
	
	Native Instruments Traktor playlist handling class.
	
	This software hasn't any connection with Native Instruments.
	
	Under development by: Andrea Bergamasco < info at vjandrea dot net >
	
	Thanks to: Philipp Burgmer www.deflip.de for the StartDate decoding.
			
----------------------------------------------------------------------*/

/*--
	
	Each found song in a playlist is loaded in a Song object. 
	The Traktor object will build an array of Song objects for further processing.
	
--*/
class Song {
	
	// title is cleaned up when required, while fullPath is raw to leave it available for filtering
	public $title;
	public $fullPath;
	
	// date and time blocks, used to build the date on the fly in the required format
	public $day;
	public $month;
	public $year;
	
	public $hours;	
	public $minutes;
	public $seconds;
	
	public $duration; // raw, to be converted on the fly to the required format
	
	public $playedPublic; // raw (boolean) Appeared only in Traktor 2.
	
	public $hideVersion; // set to true if we hide the text in brackets
	public $replaceText;	// text to replace when we remove the brackets
	
	public $log; // debug log
	
	public function __construct() {
	
		$this->hideVersion = TRUE;
		$this->replaceText = "(Remix)";
		$this->fullPath = "";
		$this->log = "\n";
	}
	
	/*--
	
		Returns true if the Song object has all requested values set.
	
	--*/
	public function isComplete() {
		$this->log .= "isComplete()\n";
		if( isset( $this->title) && !is_null( $this->title) ) {
			return TRUE;
		} else {
			return FALSE;
		}
	}
		
		
		
	/*--

		Returns true if the string parameter is found in the song path.
		Useful if you want to collect data only about songs contained in a certain folder.
		
		TODO: let it accept an array instead of a string, if we want to collect data from different folders
		TODO: develop an inverse function, that excludes the folders we set (ex. jingles / accapellas etc)

	--*/	
	public function hasValidPath( $string = "" ) {
		$this->log .= "hasValidPath('{$string}')\n";
		if( isset( $string ) && !is_null( $string ) )  {
			if( strrpos ( $this->fullPath, $string, FALSE ) > 0 ) {
				return TRUE;
			} else {
				return FALSE;
			}
		} else {
			$this->log .= "\tParameter not set, we pass everything.\n";
			return TRUE;
		}
	}
	
	
	/*--
	
		Toggles the version hiding routine
		
	--*/
	public function setHideVersion( $param = TRUE ) {
		$this->log .= "setHideVersion( {$param} )\n";
		if( isset( $param )) {
			$this->hideVersion = (bool)$param;
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	
	/*--
	
		Sets title and fullPath reading the KEY attribute
		
	--*/
	public function setKey( $string = "" ) {
		$this->log .= "setKey('{$string}')\n";
		if( isset( $string ) && trim($string) != "" ) {
			$this->fullPath = str_replace(":", "", trim($string) );
			$this->title = $this->cleanTitle( $string, $this->hideVersion, $this->replaceText );
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	
	/*--
	
		Sets $day, $month, $year reading and decoding the STARTDATE attribute
		
	--*/
	public function setStartDate( $startDate = "" ) {
		$this->log .= "setStartDate('{$startDate}')\n";
		if( isset( $startDate ) && !is_null( $startDate )) {

			$hex = dechex($startDate);
			$xYear = substr($hex, 0, 3);
			$xMonth = substr($hex, 3, 2);
			$xDay = substr($hex, 5, 2);
		
			$this->day = (int)hexdec( $xDay );
			$this->month = (int)hexdec( $xMonth );
			$this->year = (int)hexdec( $xYear );

			return TRUE;
			
		} else {
			$this->day = NULL;
			$this->month = NULL;
			$this->year = NULL;
			return FALSE;
		}
	}
	
	
	/*--
	
		Sets $hours, $minutes, $seconds reading and decoding the STARTTIME attribute
		
	--*/
	public function setStartTime( $startTime = "" ) {
		$this->log .= "setStartTime('{$startTime}')\n";
		if( isset( $startTime ) && !is_null( $startTime ) && (int)$startTime > 0 ) {
			$this->log .= "\tStartTime is valid.\n";
			
			$this->hours = (int)$startTime / 3600 % 24;
			$this->minutes = (int)$startTime / 60 % 60;
			$this->seconds = (int)$startTime % 60;		
		
			return TRUE;
		} else {
			$this->log .= "\tStartTime is not valid.\n";
		
			$this->hours = NULL;
			$this->minutes = NULL;
			$this->seconds = NULL;
			
			return FALSE;
		}
	}
	
	
	/*--
		
		Sets raw $duration reading the DURATION attribute.
		
	--*/	
	public function setDuration( $duration = 0 ) {
		$this->log .= "setDuration('{$duration}')\n";
		if( isset( $duration ) && !is_null($duration) && $duration > 0 ) {
			$this->duration = (float)$duration;
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	
	/*--
	
		Returns the song duration in the format required (default float) defined by the parameter $format
		Options:
			int
			float (default)
			time  minutes:seconds
	
	--*/
	public function getDuration( $format = "float" ) {
		$this->log .= "getDuration('{$format}')\n";
		if( isset( $format ) && !is_null( $format ) && isset( $this->duration) && !is_null( $this->duration ) ) {
			switch( $format ) {
				case "int":
					return (int)ceil( $this->duration );
				break;
				
				case "time":
					return sprintf("%d:%02d", (int)floor( $this->duration / 60 ), ((int)$this->duration)%60);
				break;
				
				case "float":
				default:
					return (float)$this->duration;
				break;
			} // switch
		} else {
			$this->log .= "\tFormat not valid: '{$format}'\n";
			return NULL;
		}
	}
	
	/*--
	
		Sets the boolean $playedPublic to true.
		This attribute appeared from version 2 of Traktor.
	
	--*/
	public function setPlayedPublic( $bool ) {
		$this->log .= "setPlayedPublic('{$bool}')\n";
		if( isset( $bool ) && !is_null( $bool ) ) {
		
			$this->playedPublic = (bool)$bool;
			
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	
	/*--
	
		Returns the song date/time in different formats, required setting the parameter $format (default "sql")
		
		sql = yyyy-mm-dd hh:mm:ss (default)
		date = yyyy-mm-dd
		time = hh:mm:ss
		timestamp = unix timestamp (mktime raw output)
	
	--*/
	public function getDate( $format = "sql" ) {
		if( isset( $format ) && !is_null( $format ) ) {
			switch( $format ) {
				case "timestamp":
					return mktime( 
						$this->hours, 
						$this->minutes, 
						$this->seconds, 
						$this->month, 
						$this->day, 
						$this->year );
				break;

				case "hour":
					return date("H:i:s", mktime( 
						$this->hours, 
						$this->minutes, 
						$this->seconds, 
						$this->month, 
						$this->day, 
						$this->year ) );
				break;

				case "date":
					return date("Y-m-d", mktime( 
						$this->hours, 
						$this->minutes, 
						$this->seconds, 
						$this->month, 
						$this->day, 
						$this->year ) );
				break;
				
				case "sql":
				default:
					return date("Y-m-d H:i:s", mktime( 
						$this->hours, 
						$this->minutes, 
						$this->seconds, 
						$this->month, 
						$this->day, 
						$this->year ) );
				break;
			} // switch
		}
	}
	
	
	/*--
		
		If you set $hideVersion to TRUE, it cuts out the text in parentheses, in order to hide the version / remix 
		(useful if you play a lot of bootlegs or private edits and you don't want to be bugged about it or you don't want to disclose these infos. )
		If you want you can replace all text in parentheses with a custom text, for example (Remix), setting the parameter $replaceText
	
	--*/
	private function cleanTitle( $string , $hideVersion = TRUE, $replaceText = "" ) {
		if( trim( $string ) == "" ) {
			return FALSE;
		} else {
			if( $hideVersion == TRUE) {
				// TODO: fix the regular expression because now if multiple parentheses are found, duplicates of $replaceText will appear in the filename.
				return preg_replace("/\((.*?)\)/", $replaceText, substr(basename( $string ), 1, -4));
			} else {
				return substr(basename( $string ), 1, -4);
			}
		}
	}
}

/*--

	We scan the history folder of Traktor in order to take out all songs we're interested to chart, and build up an array of Song objects.
	This array is used then to process the data and build the chart.

--*/

class Traktor {

	private $historyPath; // string, this points to Traktor's History folder
	private $directoryFilter; // string that has to be found in the song path in order to analyze it
	private $globalSongList; // array that contains a series of Song objects.
	public $log; // string, debug log

	
	public function __construct() {
		$historyPath = "./";
		$directoryFilter = "";
		$globalSongList = array();
		$log = "";
	}
	
	public function setHistoryPath( $path = "./" ) {
		$this->log .= "setHistoryPath('{$path}' )\n";
		if( isset( $path ) ) {
			$this->historyPath = trim( $path );
			$this->log .= "\thistoryPath set to: '{$path}'\n";
			return TRUE;
		} else {
			$this->log .= "\tInvalid path.";
			return FALSE;
		}
	}
	
	public function getHistoryPath() {
		$this->log .= "getHistoryPath()\n";
		return $this->historyPath;
	}
	
	
	public function setDirectoryFilter( $filter = "" ) {
		$this->log .= "setDirectoryFilter('{$filter}')\n";
		if( isset( $filter ) ) {
			$this->directoryFilter = trim( $filter );
			return TRUE;
		} else {
			$this->log .= "\tInvalid filter.";
			return FALSE;
		}
	}
	

	/*--
		
		Scans the current playlist and saves data in an array of Song objects.
	
	--*/
	private function scanPlaylist( $fileName = "" ) {
		$this->log .= "scanPlaylist('{$fileName}')\n";
		
		$songList = array();
		
		if( trim($fileName) == "" ) {
			$this->log .= "\tfileName not set\n";
		
			return FALSE;
		} else {
			$this->log .= "\tfileName is set\n";
		
			$xml = simplexml_load_file( $fileName ) or die("{$fileName} not loaded.\n");
			
			foreach( $xml->PLAYLISTS as $playlists ) {
				$this->log .= "\tPLAYLISTS found.\n";
				foreach( $playlists->NODE as $playlistsNode ) {
					$this->log .= "\tNODE found.\n";
					foreach( $playlistsNode->SUBNODES as $playlistsSubNode ) {
						foreach( $playlistsSubNode->NODE as $node ) {
							foreach( $node->PLAYLIST as $playlist ) {
								foreach( $playlist->ENTRY as $entry ) {
									foreach( $entry->PRIMARYKEY as $primaryKey ) {
										$this->log .= "\tPRIMARYKEY found.\n";
										
										
										/*-- we save the data collected until now in the Song object. --*/
										if( isset( $currentSong ) && is_object( $currentSong ) && $currentSong->isComplete() && $currentSong->hasValidPath( $this->directoryFilter ) ) {
											$songList[] = $currentSong;
											$this->log .= "\tSong SAVED:<em>\n";
											$this->log .= $currentSong->log;
											unset( $currentSong );
											$this->log .= "</em>\n\tSong DELETED.\n";
										} else {
											if( isset( $currentSong ) && is_object( $currentSong ) ) {
												$this->log .= "\tSong SKIPPED:<em>\n";
												$this->log .= $currentSong->log;
												unset( $currentSong );
												$this->log .= "</em>\n\tSong DELETED.\n\n";
											}
										}
										/*-- and we create a new Song object --*/
										$currentSong = new Song;
										$this->log .= "\tNew song created.\n";
										
										foreach( $primaryKey->attributes() as $name => $value ) {
											$this->log .= "\t\t{$name}: {$value}\n";
											if( $name == "KEY" ) {
												$currentSong->setKey( $value );
											}
										} // foreach
									}
									foreach( $entry->EXTENDEDDATA as $extendedData ) {
										foreach( $extendedData->attributes() as $name => $value ) {
											$this->log .= "\t\t{$name}: {$value}\n";
											if( isset( $currentSong ) && is_object( $currentSong ) ) {
												if( $name == "STARTDATE") {
													$currentSong->setStartDate( $value );
												}
												
												if( $name == "STARTTIME") {
													$this->log .= "\tSTARTTIME\n";
													$currentSong->setStartTime( $value );
												}
												
												if( $name == "DURATION") {
													$currentSong->setDuration( $value );
													$this->log .= "\tSong duration: ".$currentSong->getDuration("time")."\n";
												}
												
												if( $name == "PLAYEDPUBLIC") {
													$currentSong->setPlayedPublic( $value );
												}
											
											} // if
										} // foreach
									} // foreach
								} // foreach
							} // foreach
						} // foreach
					} // foreach
				} // foreach
			} // foreach
		} // else
		$this->log .= "\tEnd of the song list.\n";
		if( isset( $songList ) && is_array( $songList ) ) {
			return $songList;
		} else {
			return array();
		}
	}
	
	/*--
	
		Scans a directory and saves all data in the globalSongList object array
		
	--*/
	public function scanDir( $path = "" ) {
		$this->log .= "scanDir('{$path}' )\n";
		if( isset($path) && $path != '' && $path != $this->historyPath ) {
			$this->setHistoryPath( $path );
		}
		
		foreach( glob($this->historyPath."*.nml") as $nmlFilename ) {
			$this->globalSongList = array_merge( (array)$this->globalSongList, $this->scanPlaylist( $nmlFilename ) );
		}
		
		return TRUE;
	}
	
	/*--
	
		Get the full array of Song objects
		
	--*/	
	public function getGlobalSongList() {
		$this->log .= "getGlobalSongList()\n";
		if( isset( $this->globalSongList ) && !is_null( $this->globalSongList ) && is_array( $this->globalSongList ) ) {
			return $this->globalSongList;
		} else {
			return array();
		}
	}
	
	
	/*--
		
		Returns the debug log
	
	--*/
	public function getLog() {
		$this->log .= "getLog()\n";
		return $this->log;
	}
}