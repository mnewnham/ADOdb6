<?php
/**
* mysli driver session management functionality for the Sessions package
*
* This file is part of the ADOdb package.
*
* @copyright 2020 Mark Newnham
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace ADODb\session\postgres;

use \ADOdb\session;

class ADOSession extends \ADOdb\session\ADOSession {
	
	protected string $lobValue = 'null';
	
	
	final protected function getOptimizationSql(): ?string {
		
		return sprintf('VACUUM %s',$this->tableName);
	
	}
}