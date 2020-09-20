<?php
/**
* Available OEM Methods associated with the mysqli driver
*
* This file is part of the ADOdb package.
*
* @copyright 2020 Mark Newnham
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace ADOdb\database\drivers\mysqli;

final class ADOOemFlags
{
	/*
	* Allows an SSL Based connection
	*/
	public $ssl = array(
		'ssl_key'=>null,
		'ssl_cert'=>null,
		'ssl_ca' => null,
		'ssl_capath' => null,
		'ssl_cipher' => null
	);
	
	/*
	* Change the port number
	*/
	public int $port = 3306;
	
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
	public bool $multiQuery = false;
}