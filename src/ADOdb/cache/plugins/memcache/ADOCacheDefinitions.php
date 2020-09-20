<?php
/**
* Definitions Passed to the ADOCaching Module for the memcache module
*
* This file is part of the ADOdb package.
*
* @copyright 2020 Mark Newnham
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace ADOdb\cache\plugins\memcache;

/**
* Defines the attributes passed to the monolog interface
*/
final class ADOCacheDefinitions extends \ADOdb\cache\ADOCacheDefinitions
{
	/*
	* Debugging for cache
	*/
	public bool $debug = false;
	
	/*
	* Service flag. Do not modify value
	*/
	public string $service = 'memcache';
	
	/*
	* Default cache timeout
	*/
	public int $cacheSeconds = 3600;
	
	/*
	* Add one or more servers, for use in distributed systems
	* @example array('192.168.0.78', '192.168.0.79', '192.168.0.80');
	*/
	public array $memCacheHost = array();
 
	/*
	* Optionally add the server port if it differs from the default
	* @example 11211
	*/
	public ?int $memCachePort = null;
 
	/*
	* Use 'true' to store the item compressed (uses zlib)
	* Note; Compression is only supported using the memcache library. This
	* parameter will be ignored when using the memcached library
	*/
	public bool $memCacheCompress = false;
	 	
}