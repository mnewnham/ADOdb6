<?php
/**
* Available OEM Methods associated with the oci8 driver
*
* This file is part of the ADOdb package.
*
* @copyright 2020 Mark Newnham
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace ADOdb\database\drivers\oci8;

final class ADOOemFlags
{
	
		var $noNullStrings = false; /// oracle specific stuff - if true ensures that '' is converted to ' '

	
	/*
	* Set the prefetch rows for a select statement
	*/
	public int $prefetchRows = 10;
	
	/*
	* Change the socket
	*/
	public $socket = null;
	
	/*
	* For real_connect flags, e.g. MYSQLI_CLIENT_COMPRESS
	*/
	public int $flags = 0;

	/*
	* mysqli_option settings, e.g. MYSQLI_SERVER_PUBLIC_KEY
	* use Key=>Value
	*/
	public array $options = array(MYSQLI_READ_DEFAULT_GROUP=>0);
	
	/*
	* Enable multiquery. beware of sql injections
	*/
	public bool $oemFlags = false;
}