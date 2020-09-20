<?php
/**
* The main connection definitions for ADOdb sessions system
*
* This file is part of the ADOdb package.
*
* @copyright 2020 Mark Newnham
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace ADOdb\session;

final class ADOSessionDefinitions
{
		
	/*
	* Defines the debugging level
	*/
	public int $debug = 1;
	
	/*
	* Is the session connection readonly
	*/
	public bool $readOnly = false;
	
	/*
	* Defines the sessions table name
	*/
	public string $tableName = 'sessions2';
	
	/*
	* Must have a large object if we are using compression
	*/
	public ?string $largeObject = '';
	
	/*
	* What fields will be retrieved from the database on
	* read
	*/
	public string $readFields = 'sessdata';
	/*
	* Defines the crypto method. Default none
	*/
	const CRYPTO_NONE 	= 0;
	const CRYPTO_MD5  	= 1;
	const CRYPTO_MCRYPT = 2;
	const CRYPTO_SHA1   = 3;
	const CRYPTO_SECRET = 4;
	
	public int $cryptoMethod = 0;
	
	/*
	* Defines the compression method - DEfault none
	* You must use a blob field to support compressed
	* data
	*/
	const COMPRESS_NONE = 0;
	const COMPRSS_BZIP  = 1;
	const COMPRESS_GZIP = 2;

	public int $compressionMethod = 0;
	
	/*
	* Serialization methods
	*/
	const SER_DEFINED 	 	   = 0;
	const SER_PHP		 	   = 1;
	const SER_PHP_BINARY	   = 2;
	const SER_PHP_SERIALIZABLE = 3;
	const SER_PHP_WDDX 		   = 4;
	
	public ?int $serializationMethod = 3;
	
	public bool $optimizeTable = true;
	
	/**
	* The target class for session management. Extend
	* as necessary
	*/
	public ?string $sessionManagementClass = null;
	
	/**
	* Constructor 
	*
	*/
	public function __construct(){}
	
}