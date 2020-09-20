<?php
/**
* Core Methods associated with menu building
*
* This file is part of the ADOdb package.
*
* @copyright 2020 Mark Newnham
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace ADOdb\addons;

use ADOdb;
class adoMenuBuilder
{
	protected $recordset;
	
	public function __construct($recordset)
	{
		$this->recordset = $recordset;
	}
	
	/**
	 * Generate a SELECT tag string from a recordset, and return the string.
	 * If the recordset has 2 cols, we treat the 1st col as the containing
	 * the text to display to the user, and 2nd col as the return value. Default
	 * strings are compared with the FIRST column.
	 *
	 * @param name			name of SELECT tag
	 * @param [defstr]		the value to hilite. Use an array for multiple hilites for listbox.
	 * @param [blank1stItem]	true to leave the 1st item in list empty
	 * @param [multiple]		true for listbox, false for popup
	 * @param [size]		#rows to show for listbox. not used by popup
	 * @param [selectAttr]		additional attributes to defined for SELECT tag.
	 *				useful for holding javascript onChange='...' handlers.
	 & @param [compareFields0]	when we have 2 cols in recordset, we compare the defstr with
	 *				column 0 (1st col) if this is true. This is not documented.
	 *
	 * @return HTML
	 *
	 * changes by glen.davies@cce.ac.nz to support multiple hilited items
	 */
	public function getMenu($name,$defstr='',$blank1stItem=true,$multiple=false,
			$size=0, $selectAttr='',$compareFields0=true)
	{
		
		return $this->_adodb_getmenu($this->recordset, $name,$defstr,$blank1stItem,$multiple,
			$size, $selectAttr,$compareFields0);
	}

	/**
	 * Generate a SELECT tag string from a recordset, and return the string.
	 * If the recordset has 2 cols, we treat the 1st col as the containing
	 * the text to display to the user, and 2nd col as the return value. Default
	 * strings are compared with the SECOND column.
	 *
	 */
	public function getMenu2($name,$defstr='',$blank1stItem=true,$multiple=false,$size=0, $selectAttr='') {
		return $this->GetMenu($name,$defstr,$blank1stItem,$multiple,
			$size, $selectAttr,false);
	}

	/*
		Grouped Menu
	*/
	public function getMenu3($name,$defstr='',$blank1stItem=true,$multiple=false,
			$size=0, $selectAttr='')
	{
		return $this->_adodb_getmenu_gp($this, $name,$defstr,$blank1stItem,$multiple,
			$size, $selectAttr,false);
	}

	
	private function _adodb_getmenu($name,$defstr='',$blank1stItem=true,$multiple=false,
			$size=0, $selectAttr='',$compareFields0=true)
	{
		global $ADODB_FETCH_MODE;

		$s = _adodb_getmenu_select($name, $defstr, $blank1stItem, $multiple, $size, $selectAttr);

		$hasvalue = $this->recordset->FieldCount() > 1;
		if (!$hasvalue) {
			$compareFields0 = true;
		}

		$value = '';
		while(!$this->recordset->EOF) {
			$zval = rtrim(reset($this->recordset->fields));

			if ($blank1stItem && $zval == "") {
				$this->recordset->MoveNext();
				continue;
			}

			if ($hasvalue) {
				if ($ADODB_FETCH_MODE == ADODB_FETCH_ASSOC) {
					// Get 2nd field's value regardless of its name
					$zval2 = current(array_slice($this->recordset->fields, 1, 1));
				} else {
					// With NUM or BOTH fetch modes, we have a numeric index
					$zval2 = $this->recordset->fields[1];
				}
				$zval2 = trim($zval2);
				$value = 'value="' . htmlspecialchars($zval2) . '"';
			}

			$s .= _adodb_getmenu_option($defstr, $compareFields0 ? $zval : $zval2, $value, $zval);

			$this->recordset->MoveNext();
		} // while

		return $s ."\n</select>\n";
	}

	private function _adodb_getmenu_gp($name,$defstr='',$blank1stItem=true,$multiple=false,
				$size=0, $selectAttr='',$compareFields0=true)
	{
		$s = $this->_adodb_getmenu_select($name, $defstr, $blank1stItem, $multiple, $size, $selectAttr);

		$hasvalue = $this->recordset->FieldCount() > 1;
		$hasgroup = $this->recordset->FieldCount() > 2;
		if (!$hasvalue) {
			$compareFields0 = true;
		}

		$value = '';
		$optgroup = null;
		$firstgroup = true;
		while(!$this->recordset->EOF) {
			$zval = rtrim(reset($this->recordset->fields));
			$group = '';

			if ($blank1stItem && $zval=="") {
				$this->recordset->MoveNext();
				continue;
			}

			if ($hasvalue) {
				if ($this->recordset->connection->fetchMode == $this->recordset->connectionADODB::_FETCH_ASSOC) {
					// Get 2nd field's value regardless of its name
					$fields = array_slice($this->recordset->fields, 1);
					$zval2 = current($fields);
					if ($hasgroup) {
						$group = trim(next($fields));
					}
				} else {
					// With NUM or BOTH fetch modes, we have a numeric index
					$zval2 = $this->recordset->fields[1];
					if ($hasgroup) {
						$group = trim($this->recordset->fields[2]);
					}
				}
				$zval2 = trim($zval2);
				$value = "value='".htmlspecialchars($zval2)."'";
			}

			if ($optgroup != $group) {
				$optgroup = $group;
				if ($firstgroup) {
					$firstgroup = false;
				} else {
					$s .="\n</optgroup>";
				}
				$s .="\n<optgroup label='". htmlspecialchars($group) ."'>";
			}

			$s .= $this->_adodb_getmenu_option($defstr, $compareFields0 ? $zval : $zval2, $value, $zval);

			$this->recordset->MoveNext();
		} // while

		// closing last optgroup
		if($optgroup != null) {
			$s .= "\n</optgroup>";
		}
		return $s ."\n</select>\n";
	}

	/**
	 * Generate the opening SELECT tag for getmenu functions.
	 *
	 * ADOdb internal function, used by _adodb_getmenu() and _adodb_getmenu_gp().
	 *
	 * @param string $name
	 * @param string $defstr
	 * @param bool   $blank1stItem
	 * @param bool   $multiple
	 * @param int    $size
	 * @param string $selectAttr
	 *
	 * @return string HTML
	 */
	private function _adodb_getmenu_select($name, $defstr = '', $blank1stItem = true,
								   $multiple = false, $size = 0, $selectAttr = '')
	{
		if ($multiple || is_array($defstr)) {
			if ($size == 0 ) {
				$size = 5;
			}
			$attr = ' multiple size="' . $size . '"';
			if (!strpos($name,'[]')) {
				$name .= '[]';
			}
		} elseif ($size) {
			$attr = ' size="' . $size . '"';
		} else {
			$attr = '';
		}

		$html = '<select name="' . $name . '"' . $attr . ' ' . $selectAttr . '>';
		if ($blank1stItem) {
			if (is_string($blank1stItem))  {
				$barr = explode(':',$blank1stItem);
				if (sizeof($barr) == 1) {
					$barr[] = '';
				}
				$html .= "\n<option value=\"" . $barr[0] . "\">" . $barr[1] . "</option>";
			} else {
				$html .= "\n<option></option>";
			}
		}

		return $html;
	}

	/**
	 * Print the OPTION tags for getmenu functions.
	 *
	 * ADOdb internal function, used by _adodb_getmenu() and _adodb_getmenu_gp().
	 *
	 * @param string $defstr  Default values
	 * @param string $compare Value to compare against defaults
	 * @param string $value   Ready-to-print `value="xxx"` (or empty) string
	 * @param string $display Display value
	 *
	 * @return string HTML
	 */
	private function _adodb_getmenu_option($defstr, $compare, $value, $display)
	{
		if (   is_array($defstr) && in_array($compare, $defstr)
			|| !is_array($defstr) && strcasecmp($compare, $defstr) == 0
		) {
			$selected = ' selected="selected"';
		} else {
			$selected = '';
		}

		return "\n<option $value$selected>" . htmlspecialchars($display) . '</option>';
	}

	
}