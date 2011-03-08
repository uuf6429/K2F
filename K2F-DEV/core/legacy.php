<?php defined('K2F') or die;

	/**
	 * Adds missing PHP functionality.
	 * @copyright 2010 Covac Software
	 * @author Christian Sciberras
	 * @version 11/02/2010
	 * @license BSD
	 */

	if(!function_exists('get_called_class')){
		function get_called_class(){
			$main=''; $class='';
			foreach(debug_backtrace() as $trace){
				if($main!=''){
					if(!isset($trace['class']))break;
					if($trace['class']!=$main && !in_array($main,get_class_ancestors($trace['class'])))break;
					$class=$trace['class'];
				}elseif(isset($trace['class'])){
					if($trace['function']=='__construct')
						return get_class($trace['object']); // special case: constructors
					$main=$trace['class'];
				}
			}
			return $class!='' ? $class : ($main!='' ? $main : false);
		}
	}

	/**
	 * Converts to and from JSON format.
	 *
	 * JSON (JavaScript Object Notation) is a lightweight data-interchange
	 * format. It is easy for humans to read and write. It is easy for machines
	 * to parse and generate. It is based on a subset of the JavaScript
	 * Programming Language, Standard ECMA-262 3rd Edition - December 1999.
	 * This feature can also be found in  Python. JSON is a text format that is
	 * completely language independent but uses conventions that are familiar
	 * to programmers of the C-family of languages, including C, C++, C#, Java,
	 * JavaScript, Perl, TCL, and many others. These properties make JSON an
	 * ideal data-interchange language.
	 *
	 * This package provides a simple encoder and decoder for JSON notation. It
	 * is intended for use with client-side Javascript applications that make
	 * use of HTTPRequest to perform server communication functions - data can
	 * be encoded into JSON notation for use in a client-side javascript, or
	 * decoded from incoming Javascript requests. JSON format is native to
	 * Javascript, and can be directly eval()'ed with no further parsing
	 * overhead
	 *
	 * All strings should be in ASCII or UTF-8 format!
	 *
	 * LICENSE: Redistribution and use in source and binary forms, with or
	 * without modification, are permitted provided that the following
	 * conditions are met: Redistributions of source code must retain the
	 * above copyright notice, this list of conditions and the following
	 * disclaimer. Redistributions in binary form must reproduce the above
	 * copyright notice, this list of conditions and the following disclaimer
	 * in the documentation and/or other materials provided with the
	 * distribution.
	 *
	 * THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED
	 * WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
	 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN
	 * NO EVENT SHALL CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
	 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
	 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS
	 * OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
	 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR
	 * TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE
	 * USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH
	 * DAMAGE.
	 * @author      Michal Migurski <mike-json@teczno.com>
	 * @author      Matt Knapp <mdknapp[at]gmail[dot]com>
	 * @author      Brett Stimmerman <brettstimmerman[at]gmail[dot]com>
	 * @copyright   2005 Michal Migurski
	 * @version     CVS: $Id: JSON.php,v 1.31 2006/06/28 05:54:17 migurski Exp $
	 * @license     http://www.opensource.org/licenses/bsd-license.php
	 * @link        http://mike.teczno.com/json.html
	 */

	defined('JSON_HEX_TAG') or define('JSON_HEX_TAG',1);
	defined('JSON_HEX_AMP') or define('JSON_HEX_AMP',2);
	defined('JSON_HEX_APOS') or define('JSON_HEX_APOS',4);
	defined('JSON_HEX_QUOT') or define('JSON_HEX_QUOT',8);
	defined('JSON_FORCE_OBJECT') or define('JSON_FORCE_OBJECT',16);
	defined('JSON_ERROR_NONE') or define('JSON_ERROR_NONE',0);
	defined('JSON_ERROR_DEPTH') or define('JSON_ERROR_DEPTH',1);
	defined('JSON_ERROR_STATE_MISMATCH') or define('JSON_ERROR_STATE_MISMATCH',2);
	defined('JSON_ERROR_CTRL_CHAR') or define('JSON_ERROR_CTRL_CHAR',3);
	defined('JSON_ERROR_SYNTAX') or define('JSON_ERROR_SYNTAX',4);

	if(!function_exists('json_encode')){
		function json_encode($value,$options=null){
			$forceObject=bit_isset($options,JSON_FORCE_OBJECT);
			switch(gettype($value)){
				case 'boolean':
					return $value ? 'true' : 'false';
				case 'NULL':
					return 'null';
				case 'integer':
					return (int) $value;
				case 'double':
				case 'float':
					return (float) $value;
				case 'string':
					$ascii = '';
					$strlen_var = strlen($value);
					for ($c = 0; $c < $strlen_var; ++$c){
						$ord_var_c = ord($value{$c});
						switch(true){
							case $ord_var_c == 0x08:
							$ascii .= '\b';
							break;
						case $ord_var_c == 0x09:
							$ascii .= '\t';
							break;
						case $ord_var_c == 0x0A:
							$ascii .= '\n';
							break;
						case $ord_var_c == 0x0C:
							$ascii .= '\f';
							break;
						case $ord_var_c == 0x0D:
							$ascii .= '\r';
							break;
						case $ord_var_c == 0x22:
						case $ord_var_c == 0x2F:
						case $ord_var_c == 0x5C:
							// double quote, slash, slosh
							$ascii .= '\\'.$value{$c};
							break;
						case (($ord_var_c >= 0x20) && ($ord_var_c <= 0x7F)):
							// characters U-00000000 - U-0000007F (same as ASCII)
							$ascii .= $value{$c};
							break;
						case (($ord_var_c & 0xE0) == 0xC0):
							// characters U-00000080 - U-000007FF, mask 110XXXXX
							$char = pack('C*', $ord_var_c, ord($value{$c + 1}));
							$c += 1;
							$utf16 = $this->utf82utf16($char);
							$ascii .= sprintf('\u%04s', bin2hex($utf16));
							break;
						case (($ord_var_c & 0xF0) == 0xE0):
							// characters U-00000800 - U-0000FFFF, mask 1110XXXX
							$char = pack('C*', $ord_var_c,
							ord($value{$c + 1}),
							ord($value{$c + 2}));
							$c += 2;
							$utf16 = $this->utf82utf16($char);
							$ascii .= sprintf('\u%04s', bin2hex($utf16));
							break;
						case (($ord_var_c & 0xF8) == 0xF0):
							// characters U-00010000 - U-001FFFFF, mask 11110XXX
							$char = pack('C*', $ord_var_c,
							ord($value{$c + 1}),
							ord($value{$c + 2}),
							ord($value{$c + 3}));
							$c += 3;
							$utf16 = $this->utf82utf16($char);
							$ascii .= sprintf('\u%04s', bin2hex($utf16));
							break;
						case (($ord_var_c & 0xFC) == 0xF8):
							// characters U-00200000 - U-03FFFFFF, mask 111110XX
							$char = pack('C*', $ord_var_c,
							ord($value{$c + 1}),
							ord($value{$c + 2}),
							ord($value{$c + 3}),
							ord($value{$c + 4}));
							$c += 4;
							$utf16 = $this->utf82utf16($char);
							$ascii .= sprintf('\u%04s', bin2hex($utf16));
							break;
						case (($ord_var_c & 0xFE) == 0xFC):
							// characters U-04000000 - U-7FFFFFFF, mask 1111110X
							$char = pack('C*', $ord_var_c,
							ord($value{$c + 1}),
							ord($value{$c + 2}),
							ord($value{$c + 3}),
							ord($value{$c + 4}),
							ord($value{$c + 5}));
							$c += 5;
							$utf16 = $this->utf82utf16($char);
							$ascii .= sprintf('\u%04s', bin2hex($utf16));
							break;
						}
					}
					return '"'.$ascii.'"';
				case 'array':
					// treat as a JSON object
					if(is_array($value) && count($value) && (array_keys($value) !== range(0, sizeof($value) - 1)) && !$forceObject) {
						$properties = array_map(
/* -> */							array($this, 'name_value'),
							array_keys($value),
							array_values($value)
						);
						foreach($properties as $property)
							if(Services_JSON::isError($property))
								return $property;
						return '{' . join(',', $properties) . '}';
					}
					// treat it like a regular array
					$elements = array_map(array($this, 'encode'), $value);
					foreach($elements as $element)
						if(Services_JSON::isError($element))
							return $element;
					return '[' . join(',', $elements) . ']';
				case 'object':
					$values = get_object_vars($value);
					$properties = array_map(array($this, 'name_value'),
					array_keys($values),
					array_values($values));
					foreach($properties as $property)
						if(Services_JSON::isError($property))
							return $property;
					return '{' . join(',', $properties) . '}';
				default:
					return ($this->use & SERVICES_JSON_SUPPRESS_ERRORS) ? 'null'
						: new Services_JSON_Error(gettype($value)." can not be encoded as JSON string");
			}
		}
	}

?>