<?php
/**
* ADOdb Date Library, part of the ADOdb abstraction library
*
* This provides ADOdb Version 5 compatiblity, via procedure level functions
* that match the original versions
*
* This file is part of the ADOdb package.
*
* @copyright 2020 Mark Newnham
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
	
/**
* Returns the current time
*
* @return int
*/
function adodb_time() : string
{		
	$adtc = new \ADOdb\time\ADODateTimeCompatibility;
	return $adtc->adodb_time();
}

/**
* Returns day of week, 0 = Sunday,... 6=Saturday.
* Algorithm from PEAR::Date_Calc
*
* @param int $year
* @param int $month
* @param int $day
*
* @return int
*/
function adodb_dow(int $year, int $month,int $day) : int	{
	
	$adtc = new \ADOdb\time\ADODateTimeCompatibility;
	return $adtc->adodb_dow($year,$month,$day);

}


/**
* checks for leap year, returns true if it is. Has 
* 2-digit year check
*
* @param int $year
*
* @return bool
*/
function adodb_is_leap_year(int $year) : bool	{
	
	$adtc = new \ADOdb\time\ADODateTimeCompatibility;
	return  $adtc->adodb_is_leap_year($year);
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
function adodb_year_digit_check(int $y) : int	{
	
	$adtc = new \ADOdb\time\ADODateTimeCompatibility;
	return  $adtc->adodb_year_digit_check($y);
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
function adodb_get_gmt_diff(int $y,int $m,int $d) : string
{
	$adtc = new \ADOdb\time\ADODateTimeCompatibility;
	return  $adtc->adodb_get_gmt_diff($y,$m,$d);
}

/**
* Returns an array with date info based on a timestamp
*
* @param ?int $d
* @param bool $fast
*
* @return array
*/
function adodb_getdate( ?int $d=null,
						bool $fast=false) : array {
							
	if ($d === null) 
		return getdate();

	$adtc = new \ADOdb\time\ADODateTimeCompatibility;
	return  $adtc->adodb_getdate($d,$fast);
	
}

/**
* Function not described
*
* @param int $y
* @param int $m
* @param int $d
*
* @return bool
*/
function adodb_validdate(int $y,int $m,int $d) : bool {

	$adtc = new \ADOdb\time\ADODateTimeCompatibility;
	return  $adtc->adodb_validdate($y,$m,$d);

}

/**
* Function not described
*
* @param int $gmt
*
* @return string
*/
function adodb_tz_offset(int $gmt) : string
{
	$adtc = new \ADOdb\time\ADODateTimeCompatibility;
	return  $adtc->adodb_tz_offset($gmt);

}

/**
* Function not described
*
* @param str $fmt
* @param str $d
*
* @return string
*/
 function adodb_gmdate(
			string $fmt,
			?int $d=null) : string {
	
	$adtc = new \ADOdb\time\ADODateTimeCompatibility;
	return $adtc->adodb_date($fmt,$d,true);
}

/**
* accepts unix timestamp and iso date format in $d
*
* @param str $fmt
* @param str $d
*
* @return string
*/
function adodb_date2(
			string $fmt, 
			?string $d=null, 
			bool $is_gmt=false) {
	
	$adtc = new \ADOdb\time\ADODateTimeCompatibility;
	return $adtc->adodb_date2($fmt,$d,$is_gmt);
	
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
function adodb_date(
			string $fmt,
			?int $d=null,
			bool $is_gmt=false) : string {
				
	$adtc = new \ADOdb\time\ADODateTimeCompatibility;
	return $adtc->adodb_date($fmt,$d,$is_gmt);

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
* @param int? $year
* @param bool $is_dst
*
* @return int
*/
function adodb_gmmktime(
			int $hr,
			int $min,
			int $sec,
			?int $mon=null,
			?int $day=null,
			?int $year=null,
			bool $is_dst=false): int	{
	
	$adtc = new \ADOdb\time\ADODateTimeCompatibility;
	return $adtc->adodb_mktime($hr,$min,$sec,$mon,$day,$year,$is_dst,true);
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
* @param int? $year
* @param bool $is_dst
* @param bool $is_gmt
*
* @return int
*/	
function adodb_mktime(
			int $hr,
			int $min,
			int $sec,
			?int $mon=null,
			?int $day=null,
			?int $year=null,
			bool $is_dst=false,
			bool $is_gmt=false): int {

	$adtc = new \ADOdb\time\ADODateTimeCompatibility;
	return $adtc->adodb_mktime($hr,$min,$sec,$mon,$day,$year,$is_dst,$is_gmt);

}

/**
* Function not described
*
* @param str $fmt
* @param int $ts
*
* @return string
*/
 function adodb_gmstrftime(
			string $fmt, 
			?int $ts=null) : string	{
	
	$adtc = new \ADOdb\time\ADODateTimeCompatibility;
	return $adtc->adodb_strftime($fmt,$ts,true);
	
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
 function adodb_strftime(
			string $fmt, 
			?int $ts=null,
			bool $is_gmt=false) : string  {
	
	$adtc = new \ADOdb\time\ADODateTimeCompatibility;
	return $adtc->adodb_strftime($fmt,$ts,$is_gmt);

}

/**
* Returns the status of the last date calculation and whether it exceeds
* the limit of ADODB_FUTURE_DATE_CUTOFF_YEARS
*
* @return boolean
*/
function adodb_last_date_status() : bool
{
	if (!isset($GLOBALS['ADODateTime']))
		/*
		* We haven't used the class in the current procedure
		*/
		return false;
	
	return $GLOBALS['ADODateTime']->adodb_last_date_status();
}
