<?php
namespace ADOdb\database\error\lang;


class enUS extends \ADOdb\database\error\lang\ADOErrorLanguage
{
	public $ADODB_LANG_ARRAY = array (
			'LANG'                      => 'enUS',
            self::DB_ERROR                    => 'unknown error',
            self::DB_ERROR_ALREADY_EXISTS     => 'already exists',
            self::DB_ERROR_CANNOT_CREATE      => 'can not create',
            self::DB_ERROR_CANNOT_DELETE      => 'can not delete',
            self::DB_ERROR_CANNOT_DROP        => 'can not drop',
            self::DB_ERROR_CONSTRAINT         => 'constraint violation',
            self::DB_ERROR_DIVZERO            => 'division by zero',
            self::DB_ERROR_INVALID            => 'invalid',
            self::DB_ERROR_INVALID_DATE       => 'invalid date or time',
            self::DB_ERROR_INVALID_NUMBER     => 'invalid number',
            self::DB_ERROR_MISMATCH           => 'mismatch',
            self::DB_ERROR_NODBSELECTED       => 'no database selected',
            self::DB_ERROR_NOSUCHFIELD        => 'no such field',
            self::DB_ERROR_NOSUCHTABLE        => 'no such table',
            self::DB_ERROR_NOT_CAPABLE        => 'DB backend not capable',
            self::DB_ERROR_NOT_FOUND          => 'not found',
            self::DB_ERROR_NOT_LOCKED         => 'not locked',
            self::DB_ERROR_SYNTAX             => 'syntax error',
            self::DB_ERROR_UNSUPPORTED        => 'not supported',
            self::DB_ERROR_VALUE_COUNT_ON_ROW => 'value count on row',
            self::DB_ERROR_INVALID_DSN        => 'invalid DSN',
            self::DB_ERROR_CONNECT_FAILED     => 'connect failed',
            0	                       => 'no error', // self::DB_OK
            self::DB_ERROR_NEED_MORE_DATA     => 'insufficient data supplied',
            self::DB_ERROR_EXTENSION_NOT_FOUND=> 'extension not found',
            self::DB_ERROR_NOSUCHDB           => 'no such database',
            self::DB_ERROR_ACCESS_VIOLATION   => 'insufficient permissions',
            self::DB_ERROR_DEADLOCK           => 'deadlock detected',
            self::DB_ERROR_STATEMENT_TIMEOUT  => 'statement timeout',
            self::DB_ERROR_SERIALIZATION_FAILURE => 'could not serialize access'
		);
}