<?php
/**
* ADOdb Date Library, part of the ADOdb abstraction library
*
* This file is part of the ADOdb package.
*
* @copyright 2020 Mark Newnham
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace ADOdb\time;

class ADODateTime
{
	protected $_adodb_last_date_call_failed;

	protected $_month_table_normal = array('',31,28,31,30,31,30,31,31,30,31,30,31);
	protected $_month_table_leaf = array('',31,29,31,30,31,30,31,31,30,31,30,31);

	protected $dateTimeDefinitions;
	
	final public function __construct( object $dateTimeDefinitions)
	{
		$this->dateTimeDefinitions = $dateTimeDefinitions;

	}
	
	/**
	* Function not described
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/
	final public function adodb_time() : string
	{
		$d = new \DateTime();
		return $d->format('U');
	}

	/**
	*	Returns day of week, 0 = Sunday,... 6=Saturday.
	*	Algorithm from PEAR::Date_Calc
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/
	final public function adodb_dow(int $year, int $month,int $day) : int	{
		/*
		Pope Gregory removed 10 days - October 5 to October 14 - from the year 1582 and
		proclaimed that from that time onwards 3 days would be dropped from the calendar
		every 400 years.

		Thursday, October 4, 1582 (Julian) was followed immediately by Friday, October 15, 1582 (Gregorian).
		*/
		if ($year <= 1582) {
			if ($year < 1582 ||
				($year == 1582 && ($month < 10 || ($month == 10 && $day < 15)))) $greg_correction = 3;
			 else
				$greg_correction = 0;
		} else
			$greg_correction = 0;

		if($month > 2)
			$month -= 2;
		else {
			$month += 10;
			$year--;
		}

		$day =  floor((13 * $month - 1) / 5) +
				$day + ($year % 100) +
				floor(($year % 100) / 4) +
				floor(($year / 100) / 4) - 2 *
				floor($year / 100) + 77 + $greg_correction;

		return $day - 7 * floor($day / 7);
	}


	/**
	* Checks for leap year, returns true if it is. 
	* No 2-digit year check. Also
	* handles julian calendar correctly.
	*
	* @param int $year
	*
	* @return bool
	*/ 
	final protected function _adodb_is_leap_year(int $year) : bool	{
		
		if ($year % 4 != 0) return false;

		if ($year % 400 == 0) 
		{
			return true;
			/*
			* if gregorian calendar (>1582), century 
			* not-divisible by 400 is not leap
			*/
		} else if ($year > 1582 && $year % 100 == 0 ) {
			return false;
		}

		return true;
	}


	/**
	* checks for leap year, returns true if it is. Has 
	* 2-digit year check
	*
	* @param int $year
	*
	* @return bool
	*/
	final public function adodb_is_leap_year(int $year) : bool	{
		
		return  $this->_adodb_is_leap_year($this->adodb_year_digit_check($year));
	}

	/**
	* Fix 2-digit years. Works for any century.
	* Assumes that if 2-digit is more than 30 years in
	* future, then previous century.
	*
	* @param int $y
	*
	* @return int
	*/
	final public function adodb_year_digit_check(int $y) : int	{
		if ($y < 100) {

			$yr = (integer) date("Y");
			$century = (integer) ($yr /100);

			if ($yr%100 > 50) {
				$c1 = $century + 1;
				$c0 = $century;
			} else {
				$c1 = $century;
				$c0 = $century - 1;
			}
			$c1 *= 100;
			// if 2-digit year is less than 30 years in future, set it to this century
			// otherwise if more than 30 years in future, then we set 2-digit year to the prev century.
			if (($y + $c1) < $yr+30) $y = $y + $c1;
			else $y = $y + $c0*100;
		}
		return $y;
	}

	/**
	* Function not described
	*
	* @param int $ts
	* @param str $2
	*
	* @return int
	*/
	final protected function adodb_get_gmt_diff_ts( int $ts) : int	{
		
		if (0 <= $ts && $ts <= 0x7FFFFFFF) { // check if number in 32-bit signed range) {
			$arr = getdate($ts);
			$y = $arr['year'];
			$m = $arr['mon'];
			$d = $arr['mday'];
			return 	$this->adodb_get_gmt_diff($y,$m,$d);
		} else {
			return $this->adodb_get_gmt_diff(false,false,false);
		}

	}

	/**
	 get local time zone offset from GMT. Does not handle historical timezones before 1970.
	*
	* @param int $y
	* @param str $m
	* @param str $d
	*
	* @return
	*/	 
	public function adodb_get_gmt_diff(int $y,int $m,int $d) : string
	{
		static $TZ,$tzo;

		if (!defined('ADODB_TEST_DATES')) $y = false;
		else if ($y < 1970 || $y >= 2038) $y = false;

		if ($y !== false) {
			$dt = new \DateTime();
			$dt->setISODate($y,$m,$d);
			if (empty($tzo)) {
				$tzo = new \DateTimeZone(date_default_timezone_get());
			#	$tzt = timezone_transitions_get( $tzo );
			}
			return -$tzo->getOffset($dt);
		} else {
			if (isset($TZ)) return $TZ;
			$y = date('Y');
			/*
			if (function_exists('date_default_timezone_get') && function_exists('timezone_offset_get')) {
				$tzonename = date_default_timezone_get();
				if ($tzonename) {
					$tobj = new \DateTimeZone($tzonename);
					$TZ = -timezone_offset_get($tobj,new \DateTime("now",$tzo));
				}
			}
			*/
			if (empty($TZ)) 
				$TZ = mktime(0,0,0,12,2,$y) - gmmktime(0,0,0,12,2,$y);
		}
		return $TZ;
	}

	/**
	* Returns an array with date info.
	*
	* @param bool $d
	* @param bool $fast
	*
	* @return array
	*/
	final public function adodb_getdate( bool $d=false,bool $fast=false) : array {
		if ($d === false) return getdate();
		if (!defined('ADODB_TEST_DATES')) {
			if ((abs($d) <= 0x7FFFFFFF)) { // check if number in 32-bit signed range
				if (!$this->dateTimeDefinitions->noNegativeTimestamps || $d >= 0) // if windows, must be +ve integer
					return @getdate($d);
			}
		}
		return $this->_adodb_getdate($d);
	}

	/**
	* Function not described
	*
	* @param int $y
	* @param int $m
	* @param int $d
	*
	* @return
	*/
	final public function adodb_validdate(int $y,int $m,int$d) : bool {

		if ($this->_adodb_is_leap_year($y)) 
			
			$marr = $this->_month_table_leaf;
		
		else 
			$marr = $_month_table_normal;

		if ($m > 12 || $m < 1) 
			return false;

		if ($d > 31 || $d < 1) 
			return false;

		if ($marr[$m] < $d) 
			return false;

		if ($y < 1000 || $y > 3000) 
			return false;

		return true;
	}

	/**
	*	Low-level function that turns the getdate()
	* array. We have a special	$fast flag, which if set
	* to true, will return fewer array values, and is
	* much faster as it does not calculate dow, etc.
	*
	* @param bool $origd
	* @param bool $fast
	* @param bool $is_gmt
	*
	* @return array
	*/	
	
	final protected function _adodb_getdate(
				$origd=false,
				$fast=false,
				$is_gmt=false) : array {
					
		static $YRS;

		$this->_adodb_last_date_call_failed = false;

		$d =  $origd - ($is_gmt ? 0 : $this->adodb_get_gmt_diff_ts($origd));
		$_day_power = 86400;
		$_hour_power = 3600;
		$_min_power = 60;

		$cutoffDate = time() + (60 * 60 * 24 * 365 * $this->dateTimeDefinitions->futureDateCutoffYears);

		if ($d > $cutoffDate)
		{
			$d = $cutoffDate;
			$this->_adodb_last_date_call_failed = true;
		}

		if ($d < -12219321600) 
			/*
			* if 15 Oct 1582 or earlier, gregorian correction
			*/
			$d -= 86400*10; 

		$this->_month_table_normal = array("",31,28,31,30,31,30,31,31,30,31,30,31);
		$this->_month_table_leaf = array("",31,29,31,30,31,30,31,31,30,31,30,31);

		$d366 = $_day_power * 366;
		$d365 = $_day_power * 365;

		if ($d < 0) {

			if (empty($YRS)) 
				$YRS = array(
				1970 => 0,
				1960 => -315619200,
				1950 => -631152000,
				1940 => -946771200,
				1930 => -1262304000,
				1920 => -1577923200,
				1910 => -1893456000,
				1900 => -2208988800,
				1890 => -2524521600,
				1880 => -2840140800,
				1870 => -3155673600,
				1860 => -3471292800,
				1850 => -3786825600,
				1840 => -4102444800,
				1830 => -4417977600,
				1820 => -4733596800,
				1810 => -5049129600,
				1800 => -5364662400,
				1790 => -5680195200,
				1780 => -5995814400,
				1770 => -6311347200,
				1760 => -6626966400,
				1750 => -6942499200,
				1740 => -7258118400,
				1730 => -7573651200,
				1720 => -7889270400,
				1710 => -8204803200,
				1700 => -8520336000,
				1690 => -8835868800,
				1680 => -9151488000,
				1670 => -9467020800,
				1660 => -9782640000,
				1650 => -10098172800,
				1640 => -10413792000,
				1630 => -10729324800,
				1620 => -11044944000,
				1610 => -11360476800,
				1600 => -11676096000);

			if ($is_gmt) 
				$origd = $d;
			// The valid range of a 32bit signed timestamp is typically from
			// Fri, 13 Dec 1901 20:45:54 GMT to Tue, 19 Jan 2038 03:14:07 GMT
			//

			# old algorithm iterates through all years. new algorithm does it in
			# 10 year blocks

			/*
			# old algo
			for ($a = 1970 ; --$a >= 0;) {
				$lastd = $d;

				if ($leaf = _adodb_is_leap_year($a)) $d += $d366;
				else $d += $d365;

				if ($d >= 0) {
					$year = $a;
					break;
				}
			}
			*/

			$lastsecs = 0;
			$lastyear = 1970;
			foreach($YRS as $year => $secs) {
				if ($d >= $secs) {
					$a = $lastyear;
					break;
				}
				$lastsecs = $secs;
				$lastyear = $year;
			}

			$d -= $lastsecs;
			if (!isset($a)) $a = $lastyear;

			//echo ' yr=',$a,' ', $d,'.';

			for (; --$a >= 0;) {
				$lastd = $d;

				if ($leaf = $this->_adodb_is_leap_year($a)) $d += $d366;
				else $d += $d365;

				if ($d >= 0) {
					$year = $a;
					break;
				}
			}
			/**/

			$secsInYear = 86400 * ($leaf ? 366 : 365) + $lastd;

			$d = $lastd;
			$mtab = ($leaf) ? $this->_month_table_leaf : $this->_month_table_normal;
			for ($a = 13 ; --$a > 0;) {
				$lastd = $d;
				$d += $mtab[$a] * $_day_power;
				if ($d >= 0) {
					$month = $a;
					$ndays = $mtab[$a];
					break;
				}
			}

			$d = $lastd;
			$day = $ndays + ceil(($d+1) / ($_day_power));

			$d += ($ndays - $day+1)* $_day_power;
			$hour = floor($d/$_hour_power);

		} else {
			for ($a = 1970 ;; $a++) {
				$lastd = $d;

				if ($leaf = $this->_adodb_is_leap_year($a)) $d -= $d366;
				else $d -= $d365;
				if ($d < 0) {
					$year = $a;
					break;
				}
			}
			$secsInYear = $lastd;
			$d = $lastd;
			$mtab = ($leaf) ? $this->_month_table_leaf : $this->_month_table_normal;
			for ($a = 1 ; $a <= 12; $a++) {
				$lastd = $d;
				$d -= $mtab[$a] * $_day_power;
				if ($d < 0) {
					$month = $a;
					$ndays = $mtab[$a];
					break;
				}
			}
			$d = $lastd;
			$day = ceil(($d+1) / $_day_power);
			$d = $d - ($day-1) * $_day_power;
			$hour = floor($d /$_hour_power);
		}

		$d -= $hour * $_hour_power;
		$min = floor($d/$_min_power);
		$secs = $d - $min * $_min_power;
		if ($fast) {
			return array(
			'seconds' => $secs,
			'minutes' => $min,
			'hours' => $hour,
			'mday' => $day,
			'mon' => $month,
			'year' => $year,
			'yday' => floor($secsInYear/$_day_power),
			'leap' => $leaf,
			'ndays' => $ndays
			);
		}


		$dow = adodb_dow($year,$month,$day);

		return array(
			'seconds' => $secs,
			'minutes' => $min,
			'hours' => $hour,
			'mday' => $day,
			'wday' => $dow,
			'mon' => $month,
			'year' => $year,
			'yday' => floor($secsInYear/$_day_power),
			'weekday' => gmdate('l',$_day_power*(3+$dow)),
			'month' => gmdate('F',mktime(0,0,0,$month,2,1971)),
			0 => $origd
		);
	}
	/*
			if ($isphp5)
					$dates .= sprintf('%s%04d',($gmt<=0)?'+':'-',abs($gmt)/36);
				else
					$dates .= sprintf('%s%04d',($gmt<0)?'+':'-',abs($gmt)/36);
				break;*/
	
	/**
	* Function not described
	*
	* @param int $gmt
	*
	* @return string
	*/
	final public function adodb_tz_offset(int $gmt) : string
	{
		$zhrs = abs($gmt)/3600;
		$hrs = floor($zhrs);
		
		return sprintf('%s%02d%02d',($gmt<=0)?'+':'-',floor($zhrs),($zhrs-$hrs)*60);
	}

	/**
	* Function not described
	*
	* @param str $fmt
	* @param str $d
	*
	* @return string
	*/
	final public function adodb_gmdate(
				string $fmt,
				?int $d=null) : string {
					
		return adodb_date($fmt,$d,true);
	}

	/**
	* accepts unix timestamp and iso date format in $d
	*
	* @param str $fmt
	* @param str $d
	*
	* @return string
	*/
	final public function adodb_date2(
				string $fmt, 
				?string $d=null, 
				bool $is_gmt=false) {
			
		if ($d === null)
			$d = false;
		
		if ($d !== false) {
			if (!preg_match(
				"|^([0-9]{4})[-/\.]?([0-9]{1,2})[-/\.]?([0-9]{1,2})[ -]?(([0-9]{1,2}):?([0-9]{1,2}):?([0-9\.]{1,4}))?|",
				($d), $rr)) return adodb_date($fmt,false,$is_gmt);

			if ($rr[1] <= 100 && $rr[2]<= 1) return adodb_date($fmt,false,$is_gmt);

			// h-m-s-MM-DD-YY
			if (!isset($rr[5])) $d = adodb_mktime(0,0,0,$rr[2],$rr[3],$rr[1],false,$is_gmt);
			else $d = @adodb_mktime($rr[5],$rr[6],$rr[7],$rr[2],$rr[3],$rr[1],false,$is_gmt);
		}

		return adodb_date($fmt,$d,$is_gmt);
	}


	/**
	*Return formatted date based on timestamp $d
	*
	* @param str $fmt
	* @param str $d
	* @param bool $is_gmt
	*
	* @return string
	*/
	final public function adodb_date(
				string $fmt,
				?int $d=null,
				bool $is_gmt=false) : string {
					
		static $daylight;
		static $jan1_1971;

		if (!isset($daylight)) {
			
			$daylight = function_exists('adodb_daylight_sv');
			if (empty($jan1_1971)) 
				/*
				* we only use date() when > 1970 as adodb_mktime()
				* only uses mktime() when > 1970
				*/
				$jan1_1971 = mktime(0,0,0,1,1,1971); 
		}

		if ($d === false) 
			return ($is_gmt)? @gmdate($fmt): @date($fmt);
		
		if (!defined('ADODB_TEST_DATES')) {

			/*
			* Format 'Q' is an ADOdb custom format, not supported in PHP
			* so if there is a 'Q' in the format, we force it to use our
			* function. There is a trivial overhead in this
			*/

			if ((abs($d) <= 0x7FFFFFFF) && strpos($fmt,'Q') === false)
			{ // check if number in 32-bit signed range

				if (!$this->dateTimeDefinitions->noNegativeTimestamps || $d >= $jan1_1971) // if windows, must be +ve integer
					return ($is_gmt)? @gmdate($fmt,$d): @date($fmt,$d);

			}
		}
		$_day_power = 86400;

		$arr = $this->_adodb_getdate($d,true,$is_gmt);

		if ($daylight) 
			adodb_daylight_sv($arr, $is_gmt);

		$year = $arr['year'];
		$month = $arr['mon'];
		$day = $arr['mday'];
		$hour = $arr['hours'];
		$min = $arr['minutes'];
		$secs = $arr['seconds'];

		$max = strlen($fmt);
		$dates = '';

		$isphp5 = PHP_VERSION >= 5;

		/*
			at this point, we have the following integer vars to manipulate:
			$year, $month, $day, $hour, $min, $secs
		*/
		for ($i=0; $i < $max; $i++) {
			switch($fmt[$i]) {
			case 'e':
				$dates .= date('e');
				break;
			case 'T':
				$dt = new \DateTime();
				$dt->SetDate($year,$month,$day);
				$dates .= $dt->Format('T');
				break;
			// YEAR
			case 'L': $dates .= $arr['leap'] ? '1' : '0'; break;
			case 'r': // Thu, 21 Dec 2000 16:01:07 +0200

				// 4.3.11 uses '04 Jun 2004'
				// 4.3.8 uses  ' 4 Jun 2004'
				$dates .= gmdate('D',$_day_power*(3+adodb_dow($year,$month,$day))).', '
					. ($day<10?'0'.$day:$day) . ' '.date('M',mktime(0,0,0,$month,2,1971)).' '.$year.' ';

				if ($hour < 10) $dates .= '0'.$hour; else $dates .= $hour;

				if ($min < 10) $dates .= ':0'.$min; else $dates .= ':'.$min;

				if ($secs < 10) $dates .= ':0'.$secs; else $dates .= ':'.$secs;

				$gmt = adodb_get_gmt_diff($year,$month,$day);

				$dates .= ' '.adodb_tz_offset($gmt,$isphp5);
				break;

			case 'Y': $dates .= $year; break;
			case 'y': $dates .= substr($year,strlen($year)-2,2); break;
			// MONTH
			case 'm': if ($month<10) $dates .= '0'.$month; else $dates .= $month; break;
			case 'Q':
				$dates .= ceil($month / 3);
				break;
			case 'n': $dates .= $month; break;
			case 'M': $dates .= date('M',mktime(0,0,0,$month,2,1971)); break;
			case 'F': $dates .= date('F',mktime(0,0,0,$month,2,1971)); break;
			// DAY
			case 't': $dates .= $arr['ndays']; break;
			case 'z': $dates .= $arr['yday']; break;
			case 'w': $dates .= adodb_dow($year,$month,$day); break;
			case 'W':
				$dates .= sprintf('%02d',ceil( $arr['yday'] / 7) - 1);
				break;
			case 'l': $dates .= gmdate('l',$_day_power*(3+adodb_dow($year,$month,$day))); break;
			case 'D': $dates .= gmdate('D',$_day_power*(3+adodb_dow($year,$month,$day))); break;
			case 'j': $dates .= $day; break;
			case 'd': if ($day<10) $dates .= '0'.$day; else $dates .= $day; break;
			case 'S':
				$d10 = $day % 10;
				if ($d10 == 1) $dates .= 'st';
				else if ($d10 == 2 && $day != 12) $dates .= 'nd';
				else if ($d10 == 3) $dates .= 'rd';
				else $dates .= 'th';
				break;

			// HOUR
			case 'Z':
				$dates .= ($is_gmt) ? 0 : -adodb_get_gmt_diff($year,$month,$day); break;
			case 'O':
				$gmt = ($is_gmt) ? 0 : adodb_get_gmt_diff($year,$month,$day);

				$dates .= adodb_tz_offset($gmt,$isphp5);
				break;

			case 'H':
				if ($hour < 10) $dates .= '0'.$hour;
				else $dates .= $hour;
				break;
			case 'h':
				if ($hour > 12) $hh = $hour - 12;
				else {
					if ($hour == 0) $hh = '12';
					else $hh = $hour;
				}

				if ($hh < 10) $dates .= '0'.$hh;
				else $dates .= $hh;
				break;

			case 'G':
				$dates .= $hour;
				break;

			case 'g':
				if ($hour > 12) $hh = $hour - 12;
				else {
					if ($hour == 0) $hh = '12';
					else $hh = $hour;
				}
				$dates .= $hh;
				break;
			// MINUTES
			case 'i': if ($min < 10) $dates .= '0'.$min; else $dates .= $min; break;
			// SECONDS
			case 'U': $dates .= $d; break;
			case 's': if ($secs < 10) $dates .= '0'.$secs; else $dates .= $secs; break;
			// AM/PM
			// Note 00:00 to 11:59 is AM, while 12:00 to 23:59 is PM
			case 'a':
				if ($hour>=12) $dates .= 'pm';
				else $dates .= 'am';
				break;
			case 'A':
				if ($hour>=12) $dates .= 'PM';
				else $dates .= 'AM';
				break;
			default:
				$dates .= $fmt[$i]; break;
			// ESCAPE
			case "\\":
				$i++;
				if ($i < $max) $dates .= $fmt[$i];
				break;
			}
		}
		return $dates;
	}

	/**
	* Returns a timestamp given a GMT/UTC time.
	* Note that $is_dst is not implemented and is ignored.
	*
	* @param int $hr
	* @param int $min,
	* @param int $sec,
	* @param ?int $mon
	* @param ?int $day
	* @param ?int $year
	* @param bool $is_dst
	*
	* @return int
	*/
	final public function adodb_gmmktime(
				int $hr,
				int $min,
				int $sec,
				?int $mon=null,
				?int $day=null,
				?int $year=null,
				bool $is_dst=false): int	{
					
		return $this->adodb_mktime($hr,$min,$sec,$mon,$day,$year,$is_dst,true);
	}

	/**
	* Return a timestamp given a local time. Originally by jackbbs.
	* Note that $is_dst is not implemented and is ignored.
	* Not a very fast algorithm - O(n) operation. Could be optimized to O(1).
	*
	* @param int $hr
	* @param int $min,
	* @param int $sec,
	* @param ?int $mon
	* @param ?int $day
	* @param ?int $year
	* @param bool $is_dst
	* @param bool $is_gmt
	*
	* @return int
	*/	
	final public function adodb_mktime(
				int $hr,
				int $min,
				int $sec,
				?int $mon=null,
				?int $day=null,
				?int $year=null,
				bool $is_dst=false,
				bool $is_gmt=false): int	{

		if (!defined('ADODB_TEST_DATES')) {

			if ($mon === false) {
				return $is_gmt? @gmmktime($hr,$min,$sec): @mktime($hr,$min,$sec);
			}

			// for windows, we don't check 1970 because with timezone differences,
			// 1 Jan 1970 could generate negative timestamp, which is illegal
			$usephpfns = (1970 < $year && $year < 2038
				|| !$this->dateTimeDefinitions->noNegativeTimestamps && (1901 < $year && $year < 2038)
				);


			if ($usephpfns && ($year + $mon/12+$day/365.25+$hr/(24*365.25) >= 2038)) $usephpfns = false;

			if ($usephpfns) {
					return $is_gmt ?
						@gmmktime($hr,$min,$sec,$mon,$day,$year):
						@mktime($hr,$min,$sec,$mon,$day,$year);
			}
		}

		$gmt_different = ($is_gmt) ? 0 : $this->adodb_get_gmt_diff($year,$mon,$day);

		/*
		# disabled because some people place large values in $sec.
		# however we need it for $mon because we use an array...
		$hr = intval($hr);
		$min = intval($min);
		$sec = intval($sec);
		*/
		$mon = intval($mon);
		$day = intval($day);
		$year = intval($year);


		$year = $this->adodb_year_digit_check($year);

		if ($mon > 12) {
			$y = floor(($mon-1)/ 12);
			$year += $y;
			$mon -= $y*12;
		} else if ($mon < 1) {
			$y = ceil((1-$mon) / 12);
			$year -= $y;
			$mon += $y*12;
		}

		$_day_power = 86400;
		$_hour_power = 3600;
		$_min_power = 60;

		$this->_month_table_normal = array("",31,28,31,30,31,30,31,31,30,31,30,31);
		$this->_month_table_leaf = array("",31,29,31,30,31,30,31,31,30,31,30,31);

		$_total_date = 0;
		if ($year >= 1970) {
			for ($a = 1970 ; $a <= $year; $a++) {
				$leaf = $this->_adodb_is_leap_year($a);
				if ($leaf == true) {
					$loop_table = $this->_month_table_leaf;
					$_add_date = 366;
				} else {
					$loop_table = $this->_month_table_normal;
					$_add_date = 365;
				}
				if ($a < $year) {
					$_total_date += $_add_date;
				} else {
					for($b=1;$b<$mon;$b++) {
						$_total_date += $loop_table[$b];
					}
				}
			}
			$_total_date +=$day-1;
			$ret = $_total_date * $_day_power + $hr * $_hour_power + $min * $_min_power + $sec + $gmt_different;

		} else {
			for ($a = 1969 ; $a >= $year; $a--) {
				$leaf = $this->_adodb_is_leap_year($a);
				if ($leaf == true) {
					$loop_table = $this->_month_table_leaf;
					$_add_date = 366;
				} else {
					$loop_table = $this->_month_table_normal;
					$_add_date = 365;
				}
				if ($a > $year) { $_total_date += $_add_date;
				} else {
					for($b=12;$b>$mon;$b--) {
						$_total_date += $loop_table[$b];
					}
				}
			}
			$_total_date += $loop_table[$mon] - $day;

			$_day_time = $hr * $_hour_power + $min * $_min_power + $sec;
			$_day_time = $_day_power - $_day_time;
			$ret = -( $_total_date * $_day_power + $_day_time - $gmt_different);
			if ($ret < -12220185600) $ret += 10*86400; // if earlier than 5 Oct 1582 - gregorian correction
			else if ($ret < -12219321600) $ret = -12219321600; // if in limbo, reset to 15 Oct 1582.
		}
		//print " dmy=$day/$mon/$year $hr:$min:$sec => " .$ret;
		return $ret;
	}

	/**
	* Function not described
	*
	* @param str $fmt
	* @param int $ts
	*
	* @return string
	*/
	final public function adodb_gmstrftime(
				string $fmt, 
				?int $ts=null) : string	{
					
		return $this->adodb_strftime($fmt,$ts,true);
	}

	/**
	* hack - convert to adodb_date
	*
	* @param str $fmt
	* @param int $ts
	* @param bool $gmt
	*
	* @return string
	*/
	final public function adodb_strftime(
				string $fmt, 
				?int $ts=null,
				bool $is_gmt=false) : string  {

		if (!defined('ADODB_TEST_DATES')) {
			if ((abs($ts) <= 0x7FFFFFFF)) { // check if number in 32-bit signed range
				if (!$this->dateTimeDefinitions->noNegativeTimestamps || $ts >= 0) // if windows, must be +ve integer
					return ($is_gmt)? @gmstrftime($fmt,$ts): @strftime($fmt,$ts);

			}
		}

		if (!is_array($this->dateTimeDefinitions->dateLocale)) {
		/*
			$tstr = strtoupper(gmstrftime('%c',31366800)); // 30 Dec 1970, 1 am
			$sep = substr($tstr,2,1);
			$hasAM = strrpos($tstr,'M') !== false;
		*/
			# see http://phplens.com/lens/lensforum/msgs.php?id=14865 for reasoning, and changelog for version 0.24
			$dstr = gmstrftime('%x',31366800); // 30 Dec 1970, 1 am
			$sep = substr($dstr,2,1);
			$tstr = strtoupper(gmstrftime('%X',31366800)); // 30 Dec 1970, 1 am
			$hasAM = strrpos($tstr,'M') !== false;

			$this->dateTimeDefinitions->dateLocale = array();
			$this->dateTimeDefinitions->dateLocale[] =  strncmp($tstr,'30',2) == 0 ? 'd'.$sep.'m'.$sep.'y' : 'm'.$sep.'d'.$sep.'y';
			$this->dateTimeDefinitions->dateLocale[]  = ($hasAM) ? 'h:i:s a' : 'H:i:s';

		}
		$inpct = false;
		$fmtdate = '';
		for ($i=0,$max = strlen($fmt); $i < $max; $i++) {
			$ch = $fmt[$i];
			if ($ch == '%') {
				if ($inpct) {
					$fmtdate .= '%';
					$inpct = false;
				} else
					$inpct = true;
			} else if ($inpct) {

				$inpct = false;
				switch($ch) {
				case '0':
				case '1':
				case '2':
				case '3':
				case '4':
				case '5':
				case '6':
				case '7':
				case '8':
				case '9':
				case 'E':
				case 'O':
					/* ignore format modifiers */
					$inpct = true;
					break;

				case 'a': 
					$fmtdate .= 'D'; 
					break;
				case 'A': 
					$fmtdate .= 'l';
					break;
				case 'h':
				case 'b': 
					$fmtdate .= 'M'; 
					break;
				case 'B': 
					$fmtdate .= 'F';
					break;
				case 'c': 
				$fmtdate .= sprintf('%s%s',
					$this->dateTimeDefinitions->dateLocale[0],
					$this->dateTimeDefinitions->dateLocale[1]);
					break;
				case 'C': 
					$fmtdate .= '\C?'; 
					break; // century
				case 'd': 
					$fmtdate .= 'd';
					break;
				case 'D':
					$fmtdate .= 'm/d/y'; 
					break;
				case 'e': 
					$fmtdate .= 'j';
					break;
				case 'g': 
					$fmtdate .= '\g?';
					break; //?
				case 'G': 
					$fmtdate .= '\G?'; 
					break; //?
				case 'H': 
					$fmtdate .= 'H';
					break;
				case 'I': 
					$fmtdate .= 'h';
					break;
				case 'j': 
					$fmtdate .= '?z';
					$parsej = true;
					break; // wrong as j=1-based, z=0-basd
				case 'm': 
					$fmtdate .= 'm';
					break;
				case 'M': 
					$fmtdate .= 'i';
					break;
				case 'n': 
					$fmtdate .= "\n";
					break;
				case 'p':
					$fmtdate .= 'a';
					break;
				case 'r': 
					$fmtdate .= 'h:i:s a';
					break;
				case 'R': 
					$fmtdate .= 'H:i:s';
					break;
				case 'S': 
					$fmtdate .= 's';
					break;
				case 't': 
					$fmtdate .= "\t";
					break;
				case 'T': 
					$fmtdate .= 'H:i:s';
					break;
				case 'u':
					$fmtdate .= '?u'; 
					$parseu = true; 
					break; // wrong strftime=1-based, date=0-based
				case 'U': 
					$fmtdate .= '?U'; 
					$parseU = true;
					break;// wrong strftime=1-based, date=0-based
				case 'x': 
					$fmtdate .= $this->dateTimeDefinitions->dateLocale[0]; 
					break;
				case 'X': 
					$fmtdate .= $this->dateTimeDefinitions->dateLocale[1];
					break;
				case 'w': 
					$fmtdate .= '?w'; 
					$parseu = true;
					break; // wrong strftime=1-based, date=0-based
				case 'W': 
					$fmtdate .= '?W'; 
					$parseU = true; 
					break;// wrong strftime=1-based, date=0-based
				case 'y': 
					$fmtdate .= 'y';
					break;
				case 'Y': 
					$fmtdate .= 'Y';
					break;
				case 'Z': 
					$fmtdate .= 'T';
					break;
				}
			} else if (('A' <= ($ch) && ($ch) <= 'Z' ) 
					|| ('a' <= ($ch) && ($ch) <= 'z' ))
				$fmtdate .= "\\".$ch;
			else
				$fmtdate .= $ch;
		}
		//echo "fmt=",$fmtdate,"<br>";
		if ($ts === false) $ts = time();
		$ret = $this->adodb_date($fmtdate, $ts, $is_gmt);
		return $ret;
	}

	/**
	* Returns the status of the last date calculation and whether it exceeds
	* the limit of ADODB_FUTURE_DATE_CUTOFF_YEARS
	*
	* @return boolean
	*/
	final public function adodb_last_date_status() : bool
	{
		return $this->_adodb_last_date_call_failed;
	}
}