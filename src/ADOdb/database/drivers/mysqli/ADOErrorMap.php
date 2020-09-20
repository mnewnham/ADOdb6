<?php
/**
* The ADOdb Error map associated with the mysqli driver
*
* This file is part of the ADOdb package.
*
* @copyright 2020 Mark Newnham
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace ADODb\database\drivers\mysqli;

class ADOErrorMap extends \ADOdb\database\error\ADOErrorMap{

	public $MAP = array(
	   1004 => self::DB_ERROR_CANNOT_CREATE,
	   1005 => self::DB_ERROR_CANNOT_CREATE,
	   1006 => self::DB_ERROR_CANNOT_CREATE,
	   1007 => self::DB_ERROR_ALREADY_EXISTS,
	   1008 => self::DB_ERROR_CANNOT_DROP,
	   1045 => self::DB_ERROR_ACCESS_VIOLATION,
	   1046 => self::DB_ERROR_NODBSELECTED,
	   1049 => self::DB_ERROR_NOSUCHDB,
	   1050 => self::DB_ERROR_ALREADY_EXISTS,
	   1051 => self::DB_ERROR_NOSUCHTABLE,
	   1054 => self::DB_ERROR_NOSUCHFIELD,
	   1062 => self::DB_ERROR_ALREADY_EXISTS,
	   1064 => self::DB_ERROR_SYNTAX,
	   1100 => self::DB_ERROR_NOT_LOCKED,
	   1136 => self::DB_ERROR_VALUE_COUNT_ON_ROW,
	   1146 => self::DB_ERROR_NOSUCHTABLE,
	   1048 => self::DB_ERROR_CONSTRAINT,
	   2002 => self::DB_ERROR_CONNECT_FAILED,
	   2005 => self::DB_ERROR_CONNECT_FAILED
    );
}