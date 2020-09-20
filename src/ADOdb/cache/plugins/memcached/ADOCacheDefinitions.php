<?php
/**
* Definitions Passed to the ADOCaching Module for the memcached module
*
* This file is part of the ADOdb package.
*
* @copyright 2020 Mark Newnham
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace ADOdb\cache\plugins\memcached;

/**
* Defines the attributes passed to the monolog interface
*/
final class ADOCacheDefinitions extends \ADOdb\cache\ADOCacheDefinitions
{
	/*
	* Debugging for cache
	*/
	public bool $debug = true;
	
	/*
	* Service flag. Do not modify value
	*/
	public string $service = 'memcached';
	
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
	* Can now use the servers option for memcached, can specify
	* host, port and optionally weight for a group of controllers
	*
	* 'host'=>192.68.0.85','port'=>'11261','weight'=>66
	*/
	public ?array $memCacheControllers = null;
	
	
	/*
	* Can optionally be used to set memcached server options
	*/
	public ?array $memCacheOptions = null;
 	
}