<?php
/**
* ADOdb Date Library, part of the ADOdb abstraction library
*
* This provides ADOdb Version 5 compatiblity, when used with ADOdbDateTimeCore
*
* This file is part of the ADOdb package.
*
* @copyright 2020 Mark Newnham
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace ADOdb\time;

include_once 'vendor/adodb/adodb/src/ADOdb/time/ADODateTimeCore.inc';

class ADODateTimeCompatibility
{

	protected $dateTimeDefinitions;
	protected $dateTimeClass;
	
	
	final public function __construct()
	{
		/*
		* Legacy locale support, now dateLocale
		*/
		global $ADODB_DATE_LOCALE;
		
		/*
		* We take the nomal date time definitions and see if there are any
		* constants or globals that might affect the setup
		*/
		$dateTimeDefinitions = new \ADOdb\common\ADODateTimeDefinitions;		
		
		if ($ADODB_DATE_LOCALE)
			$dateTimeDefinitions->dateLocale = $ADODB_DATE_LOCALE;
		
		if (defined('ADODB_FUTURE_DATE_CUTOFF_YEARS'))
			$dateTimeDefinitions->futureDateCutoffYears = ADODB_FUTURE_DATE_CUTOFF_YEARS;
		
		if (defined('ADODB_NO_NEGATIVE_TS'))
			$dateTimeDefinitions->noNegativeTimestamps = ADODB_NO_NEGATIVE_TS;
		
		/*
		* If there has been any change in legacy parameters, we rebuild
		* the class with the new values
		*/
		$globalsHashCheck = md5(json_encode($dateTimeDefinitions));
		
		if (isset($GLOBALS['ADODateTime'])
		&& is_object($GLOBALS['ADODateTime'])
		&& isset($GLOBALS['ADODateTime']->globalsHashCheck)
		&& $GLOBALS['ADODateTime']->globalsHashCheck == $globalsHashCheck)
			$this->dateTimeClass = $GLOBALS['ADODateTime'];
		else
		{
			/*
			* Build a class, that we are going to cache in globals so that we can re-use it
			*/
			$this->dateTimeClass = new \ADOdb\time\ADODateTime($dateTimeDefinitions);
			$this->dateTimeClass->globalsHashCheck = $globalsHashCheck;
			$GLOBALS['ADODateTime'] = &$this->dateTimeClass;
		}
			
	}
	
	final public function __call($name, $arguments)
    {
        // Note: value of $name is case sensitive.
        //echo "\nCalling object method '$name' "
        //     . implode(', ', $arguments). "\n";
		
		$name = strtolower($name);
		
		if (!method_exists ($this->dateTimeClass,$name))
			return null;
		
		
		switch($name) {
			case 'adodb_time':
			return $this->dateTimeClass->adodb_time();
			case 'adodb_dow':
			return $this->dateTimeClass->adodb_dow(
						$arguments[0],
						$arguments[1],
						$arguments[2]);
			case 'adodb_is_leap_year':
			return $this->dateTimeClass->adodb_is_leap_year(
						$arguments[0]);
			case 'adodb_year_digit_check':
			return $this->dateTimeClass->adodb_year_digit_check(
						$arguments[0]);
			case 'adodb_get_gmt_diff':
			return $this->dateTimeClass->adodb_get_gmt_diff(
						$arguments[0],
						$arguments[1],
						$arguments[2]);
			case 'adodb_getdate':
			return $this->dateTimeClass->adodb_getdate(
						$arguments[0],
						$arguments[1]);
						
			case 'adodb_validdate':
			return $this->dateTimeClass->adodb_validdate(
						$arguments[0],
						$arguments[1],
						$arguments[2]);
			case 'adodb_tz_offset':
			return $this->dateTimeClass->adodb_tz_offset(
						$arguments[0]);
			case 'adodb_date':
			case 'adodb_gmdate':
			return $this->dateTimeClass->adodb_date(
						$arguments[0],
						$arguments[1],
						$arguments[2]);
			case 'adodb_date2':
			return $this->dateTimeClass->adodb_date2(
						$arguments[0],
						$arguments[1],
						$arguments[2]);
			case 'adodb_mktime':
			case 'adodb_gmmktime':
			return $this->dateTimeClass->adodb_mktime(
						$arguments[0],
						$arguments[1],
						$arguments[2],
						$arguments[3],
						$arguments[4],
						$arguments[5],
						$arguments[6],
						$arguments[7]
						);
			case 'adodb_strftime':
			case 'adodb_gmstrftime':
			return $this->dateTimeClass->adodb_strftime(
						$arguments[0],
						$arguments[1],
						$arguments[2]);
			default:
			return null;
		}
    }
}
