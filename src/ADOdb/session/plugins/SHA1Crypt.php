<?php
/**
* SHA1 Encryption session management plugin for the Sessions package
*
* This file is part of the ADOdb package.
*
* @copyright 2020 Mark Newnham
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace ADODb\session\plugins;
use \ADOdb\session;

class SHA1Crypt extends \ADOdb\session\plugins\ADOCrypt{

	/**
	* Fetches the encryption key for the scheme
	*
	* return string
	*/
	final protected function fetchEncryptionKey() : string {
	
		return sha1(rand(0,32000));
	
	}	
}
