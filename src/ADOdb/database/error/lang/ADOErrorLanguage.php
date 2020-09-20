<?php
namespace ADODb\database\error\lang;

class ADOErrorLanguage
{
	const DB_ERROR = -1;
	const DB_ERROR_SYNTAX =              -2;
	const DB_ERROR_CONSTRAINT =          -3;
	const DB_ERROR_NOT_FOUND =           -4;
	const DB_ERROR_ALREADY_EXISTS =      -5;
	const DB_ERROR_UNSUPPORTED =         -6;
	const DB_ERROR_MISMATCH =            -7;
	const DB_ERROR_INVALID =             -8;
	const DB_ERROR_NOT_CAPABLE =         -9;
	const DB_ERROR_TRUNCATED =          -10;
	const DB_ERROR_INVALID_NUMBER =     -11;
	const DB_ERROR_INVALID_DATE =       -12;
	const DB_ERROR_DIVZERO =            -13;
	const DB_ERROR_NODBSELECTED =       -14;
	const DB_ERROR_CANNOT_CREATE =      -15;
	const DB_ERROR_CANNOT_DELETE =      -16;
	const DB_ERROR_CANNOT_DROP =        -17;
	const DB_ERROR_NOSUCHTABLE =        -18;
	const DB_ERROR_NOSUCHFIELD =        -19;
	const DB_ERROR_NEED_MORE_DATA =     -20;
	const DB_ERROR_NOT_LOCKED 			= -21;
	const DB_ERROR_VALUE_COUNT_ON_ROW 	= -22;
	const DB_ERROR_INVALID_DSN =        -23;
	const DB_ERROR_CONNECT_FAILED =     -24;
	const DB_ERROR_EXTENSION_NOT_FOUND =-25;
	const DB_ERROR_NOSUCHDB =           -25;
	const DB_ERROR_ACCESS_VIOLATION =   -26;
	const DB_ERROR_DEADLOCK =           -27;
	const DB_ERROR_STATEMENT_TIMEOUT =  -28;
	const DB_ERROR_SERIALIZATION_FAILURE = -29; 			

	protected $ADODB_LANG_ARRAY = array ();
}