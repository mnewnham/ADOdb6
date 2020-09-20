<?php
/**
* Definitions Passed to the ADOCaching Module
*
* This file is part of the ADOdb package.
*
* @copyright 2020 Mark Newnham
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace ADOdb\cache;

/**
* Defines the attributes passed to the monolog interface
*/
class ADOCacheDefinitions
{
	/*
	* Debugging for cache
	*/
	public bool $debug = true;
	
	/*
	* Service flag. Do not modify value
	*/
	public string $service = '';
	
	/*
	* Default cache timeout
	*/
	public int $cacheSeconds = 3600;
 	
}