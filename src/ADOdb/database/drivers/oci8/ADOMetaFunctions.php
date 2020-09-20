<?php
namespace ADOdb\database\drivers\oci8;

class ADOMetaFunctions extends \ADOdb\meta\ADOMetaFunctions
{
	
	final public function metaTransaction($mode) {
		$mode = strtoupper($mode);
		$mode = str_replace('ISOLATION LEVEL ','',$mode);

		switch($mode) {

		case 'READ UNCOMMITTED':
			return 'ISOLATION LEVEL READ COMMITTED';
			break;

		case 'READ COMMITTED':
			return 'ISOLATION LEVEL READ COMMITTED';
			break;

		case 'REPEATABLE READ':
			return 'ISOLATION LEVEL SERIALIZABLE';
			break;

		case 'SERIALIZABLE':
			return 'ISOLATION LEVEL SERIALIZABLE';
			break;

		default:
			return $mode;
		}
	}


}