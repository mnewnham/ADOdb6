<?php
/**
* Helper class for FetchFields -- holds info on a column This field
* extended for each type and database
*
* This file is part of the ADOdb package.
*
* @copyright 2020 Mark Newnham
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace ADOdb\common;
use ADOdb;

final class ADOFieldObject 
{
	/*
	* Common definitions
	*/
	public ?string $name = null;
	public int $max_length=0;
	public ?string $type=null;
	public ?string $metatype=null;
}