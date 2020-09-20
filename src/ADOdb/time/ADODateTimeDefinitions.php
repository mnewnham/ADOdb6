<?php
/**
* Definitions Passed to the ADODateTime Module
*
* This file is part of the ADOdb package.
*
* @copyright 2020 Mark Newnham
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace ADOdb\time;

/**
* Defines the attributes passed to the date/time interface
*/
final class ADODateTimeDefinitions
{
	/*
	* Sane Default Options
	*/
	
	/* 
	* What to display when the passed time is 0
	*/
	public ?string $emptyTimeStamp = null;
	
	/* 
	* What to display when the user date is empty
	*/
	public ?string $emptyDate = null;

	/*
	* The ADODB_DATE_LOCALE option
	*/
	public ?string $dateLocale = null;
	
	/*
	* The ADODB_FUTURE_DATE_CUTOFF_YEARS option
	*/
	public int $futureDateCutoffYears   = 200;
	
	/*
	* The ADODB_NO_NEGATIVE_TS option
	*/
	public int $noNegativeTimestamps = 1;
	
	/*
	* The first year number for ADOdb time functions
	*/
	public int $timestampFirstYear = 100;
	
}