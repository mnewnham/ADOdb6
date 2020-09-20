<?php
/**
* Methods associated with Database Date/Time Functions
*
* This file is part of the ADOdb package.
*
* @copyright 2020 Mark Newnham
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace ADOdb\addons;

class ADODbDateTimeFunctions
{
	/*
	* Placeholders
	*/
	protected $dateTimeDefinitions;
	protected $dateTimeClass;
	protected $connection;
	
	/*
	* Constructor
	*
	* @param obj	$connection
	*/
	public function __construct($connection)
	{	
		$this->connection = $connection;
		$this->dateTimeDefinitions = $connection->connectionDefinitions->dateTimeDefinitions;
		
		/*
		* We use this a lot, just instantiate
		*/
		$this->dateTimeClass = new \ADOdb\time\ADODateTime($this->dateTimeDefinitions);

	}
	
	/**
	* Retuns a date usable in a bind array
	*
	* @param str $d
	*
	* @return str
	*/
	public function bindDate(string $d) : string {
		
		$d = $this->dbDate($d);
		if (strncmp($d,"'",1)) {
			return $d;
		}

		return substr($d,1,strlen($d)-2);
	}

	/**
	* Retuns a timestamp usable in a bind array
	*
	* @param str $d 
	*
	* @return str
	*/
	public function bindTimeStamp(string $d) : string {
		
		$d = $this->dbTimeStamp($d);
		if (strncmp($d,"'",1)) {
			return $d;
		}
		return substr($d,1,strlen($d)-2);
	}
	
	/**
	* Converts a timestamp "ts" to a string that the database can understand.
	*
	* @param ts	a timestamp in Unix date time format.
	*
	* @return  timestamp string in database timestamp format
	*/
	public function dbTimeStamp(int $ts,bool $isfld=false) : string{
		
		if (empty($ts) && $ts !== 0) {
			return 'null';
		}
		if ($isfld) {
			return $ts;
		}
		
		if (is_object($ts)) {
			return $ts->format($this->connection->fmtTimeStamp);
		}

		# strlen(14) allows YYYYMMDDHHMMSS format
		if (!is_string($ts) || (is_numeric($ts) && strlen($ts)<14)) {
			return $this->dateTimeClass->adodb_date($this->connection->fmtTimeStamp,$ts);
		}

		if ($ts === 'null') {
			return $ts;
		}
		if ($this->isoDates && strlen($ts) !== 14) {
			$ts = $this->dateTimeClass->_adodb_safedate($ts);
			return "'$ts'";
		}
		$ts = $this->unixTimeStamp($ts);
		return $this->dateTimeClass->adodb_date($this->connection->fmtTimeStamp,$ts);
	}
	
	/**
	 * Also in ADORecordSet.
	 * @param string $v is a date string in YYYY-MM-DD format
	 *
	 * @return date in unix timestamp format, or 0 if before TIMESTAMP_FIRST_YEAR, or false if invalid date format
	 */
	final public function unixDate(string $v) : int	{
		if (is_object($v)) {
		// odbtp support
		//( [year] => 2004 [month] => 9 [day] => 4 [hour] => 12 [minute] => 44 [second] => 8 [fraction] => 0 )
			return $this->dateTimeClass->adodb_mktime($v->hour,$v->minute,$v->second,$v->month,$v->day, $v->year);
		}

		if (is_numeric($v) && strlen($v) !== 8) {
			return $v;
		}
		if (!preg_match( "|^([0-9]{4})[-/\.]?([0-9]{1,2})[-/\.]?([0-9]{1,2})|", $v, $rr)) {
			return false;
		}

		if ($rr[1] <= $this->dateTimeDefinitions->timestampFirstYear) {
			return 0;
		}

		// h-m-s-MM-DD-YY
		return$this->dateTimeClass->adodb_mktime(0,0,0,$rr[2],$rr[3],$rr[1]);
	}
	
	/**
	* Returns a date as a timestamp
	*
	* @param str $v some form of date string
	*
	* @return int
	*/
	final public function unixTimeStamp(string $v) : int {
		
		if (is_object($v)) {
		// odbtp support
		//( [year] => 2004 [month] => 9 [day] => 4 [hour] => 12 [minute] => 44 [second] => 8 [fraction] => 0 )
			return $this->dateTimeClass->adodb_mktime($v->hour,$v->minute,$v->second,$v->month,$v->day, $v->year);
		}

		if (!preg_match(
			"|^([0-9]{4})[-/\.]?([0-9]{1,2})[-/\.]?([0-9]{1,2})[ ,-]*(([0-9]{1,2}):?([0-9]{1,2}):?([0-9\.]{1,4}))?|",
			($v), $rr)) return false;

		if ($rr[1] <= $this->dateTimeDefinitions->timestampFirstYear && $rr[2]<= 1) {
			return 0;
		}

		// h-m-s-MM-DD-YY
		if (!isset($rr[5])) {
			
			return  $this->dateTimeClass->adodb_mktime(0,0,0,$rr[2],$rr[3],$rr[1]);
		}
		return $this->dateTimeClass->adodb_mktime($rr[5],$rr[6],$rr[7],$rr[2],$rr[3],$rr[1]);
	}
	
	/**
	 * Also in ADORecordSet.
	 *
	 * Format database date based on user defined format.
	 *
	 * @param 	string	$v		is the character timestamp in YYYY-MM-DD hh:mm:ss format
	 * @param 	string	$fmt	is the format to apply to it, using date()
	 * @param	bool	$gmt
	 *
	 * @return a date formated as user desires
	 */
	final public function userDate(
					string $v,
					string $fmt='Y-m-d',
					bool $gmt=false) : ?string {
		
		$tt = $this->unixDate($v);

		// $tt == -1 if pre TIMESTAMP_FIRST_YEAR
		if (($tt === false || $tt == -1) && $v != false) {
			return $v;
		} else if ($tt == 0) {
			return $this->dateTimeDefinitions->emptyDate;
		} else if ($tt == -1) {
			// pre-TIMESTAMP_FIRST_YEAR
		}
		
		return ($gmt) ? $this->dateTimeClass->adodb_gmdate($fmt,$tt) : $this->dateTimeClass->adodb_date($fmt,$tt);

	}
	
	/**
	 *
	 * @param 	string	$v		is the character timestamp in YYYY-MM-DD hh:mm:ss format
	 * @param 	string	$fmt	is the format to apply to it, using date()
	 * @param	bool	$gmt
	 *
	 * @return a timestamp formated as user desires
	 */
	final public function userTimeStamp(
					string $v,
					string $fmt='Y-m-d H:i:s',
					bool $gmt=false) : ?string {
		
		if (!isset($v)) {
			return $this->dateTimeDefinitions->emptyTimeStamp;
		}
		# strlen(14) allows YYYYMMDDHHMMSS format
		if (is_numeric($v) && strlen($v)<14) {
			return ($gmt) ? $this->dateTimeClass->adodb_gmdate($fmt,$v) : $this->dateTimeClass->adodb_date($fmt,$v);
		}
		$tt = $this->unixTimeStamp($v);
		// $tt == -1 if pre TIMESTAMP_FIRST_YEAR
		if (($tt === false || $tt == -1) && $v != false) {
			return $v;
		}
		if ($tt == 0) {
			return $this->dateTimeDefinitions->emptyTimeStamp;
		}
		return ($gmt) ? $this->dateTimeClass->adodb_gmdate($fmt,$tt) : $this->dateTimeClass->adodb_date($fmt,$tt);
	}
	
	/**
	* Returns a date math calculation
	*
	* @param int $dayFraction
	* @param str $date, an SQL date string
	*
	* @return string
	*/
	final public function offsetDate(int $dayFraction, ?string $date=null) : string	{
		if (!$date) {
			$date = $this->sysDate;
		}
		
		return  '('.$date.'+'.$dayFraction.')';
	}
	
	/**
	 * Converts a date "d" to a string that the database can understand.
	 *
	 * @param 	int		$d		a date in Unix date time format.
	 * @param	bool	$isfld
	 *
	 * @return  date string in database date format
	 */
	public function dbDate(?string $d, bool $isfld=false) : string	{
				
		if (empty($d) && $d !== 0) {
			return 'null';
		}
		if ($isfld) {
			return $d;
		}
		if (is_object($d)) {
			return $d->format($this->connection->fmtDate);
		}

		if (is_string($d) && !is_numeric($d)) {
			if ($d === 'null') {
				return $d;
			}
			if (strncmp($d,"'",1) === 0) {
				$d = $this->_adodb_safedateq($d);
				return $d;
			}
			if ($this->connection->handlesIsoDates()) {
				return "'$d'";
			}
			$d = $this->unixDate($d);
		}

		return $this->dateTimeClass->adodb_date($this->connection->fmtDate,$d);
	}
	
	/**
	* Function not described
	*
	* @param str $s
	*
	* @return str
	*/
	final protected function _adodb_safedate(string $s) : string {
		return str_replace(array("'", '\\'), '', $s);
	}

	/**
	* parse date string to prevent injection attack
	* date string will have one quote at beginning e.g. '3434343'
	*
	* @param str $s
	*
	* @return str
	*/
	final protected function _adodb_safedateq(string $s) : string {
		
		$len = strlen($s);
		if ($s[0] !== "'") {
			$s2 = "'".$s[0];
		} else {
			$s2 = "'";
		}
		for($i=1; $i<$len; $i++) {
			$ch = $s[$i];
			if ($ch === '\\') {
				$s2 .= "'";
				break;
			} elseif ($ch === "'") {
				$s2 .= $ch;
				break;
			}

			$s2 .= $ch;
		}

		return strlen($s2) == 0 ? 'null' : $s2;
	}

}