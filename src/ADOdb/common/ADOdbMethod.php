<?php
/**
* Method template for functions turned into classes
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

final class ADOdbMethod
{

	/*
	* The result of the function that was executed
	*/
	protected $methodResult;

	
	/**
	* Returns the result of the method
	*
	* @return mixed
	*/
	final public function getResult()
	{
		return $this->methodResult;
	}
	
}