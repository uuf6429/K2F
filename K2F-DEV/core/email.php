<?php defined('K2F') or die;

	uses('core/security.php','core/mime.php');

	/**
	 * A class for easily sending mail.
	 * @copyright 2010 Covac Software
	 * @author Christian Sciberras
	 * @version 14/12/2010
	 */
	class Email {
		/**
		 * Email sent successfully.
		 */
		const ST_SUCCESS=1;
		/**
		 * Generic email failure.
		 */
		const ST_FAIL_GENERIC=0;
		/**
		 * @var string The sender address. Eg: lottery@hsdc.com
		 */
		public $from='';
		/**
		 * @var string The recieving address. Eg: test@gmail.com
		 */
		public $to='';
		/**
		 * @var string Similar to from, this is the address replies are sent to. Eg: harvest@hsdc.com
		 */
		public $reply='';
		/**
		 * @var string The email subject. Eg: You won $1M!!!
		 */
		public $subject='';
		/**
		 * @var string The email's content in HTML. Eg: You just won Bank Of Africa lottery at Burkina Faso!
		 */
		public $html='';
		/**
		 * @var array An array of files to be attached (keys are the file name and values are the file data).
		 */
		public $attachments=array();
		/**
		 * Creates a new email instance.
		 * @param string $from The sender address. Eg: lottery@hsdc.com
		 * @param string $to The recieving address. Eg: test@gmail.com
		 * @param string $reply Similar to from, this is the address replies are sent to. Eg: harvest@hsdc.com
		 * @param string $subject The email subject. Eg: You won $1M!!!
		 * @param string $html The email's content in HTML. Eg: You just won Bank Of Africa lottery at Burkina Faso!
		 * @param array $attachments An array of files to be attached (keys are the file name and values are the file data).
		 */
		public function __construct($from='',$to='',$reply='',$subject='',$html='',$attachments=array()){
			$this->from=$from;
			$this->to=$to;
			$this->reply=$reply;
			$this->subject=$subject;
			$this->html=$html;
			$this->attachments=$attachments;
		}
		/**
		 * Send the email and return status.
		 * @return integer A value to denote success or failure. (see Email::ST_* constants).
		 */
		public function send(){
			$hash='g5687627sg-'.md5(date('r',time())).'-vdfgq45uj7';
			$text=self::_htmltotext($this->html);
			$headers=array(
				'From: '.$this->from,
				'Reply-To: '.$this->reply,
				'Content-Type: multipart/alternative; boundary="'.$hash.'"',
			);
			$message=array(
				'',
				'--'.$hash,
				'Content-Type: text/plain; charset="iso-8859-1"',
				'Content-Transfer-Encoding: 7bit',
				'',$text,'',
				'--'.$hash,
				'Content-Type: text/html; charset="iso-8859-1"',
				'Content-Transfer-Encoding: 7bit',
				'',$this->html,''
			);
/////////////////////////////////////////////////
			foreach($this->attachments as $name=>$data){
				$message[]='--'.$hash;
				$message[]='Content-Type: '.MimeTypes::get_extension_mimetype($name).'; name="'.str_replace(array('"',CR,LF),'_',$name).'"';
				$message[]='Content-Transfer-Encoding: base64';
				$message[]='Content-Disposition: attachment';
				$message[]='';
				$message[]=chunk_split(base64_encode($data));
			}
/////////////////////////////////////////////////
			$message[]='--'.$hash.'--';
			// Note: PHP_EOL is used instead of CRLF. see http://www.php.net/manual/en/function.mail.php#100563
			return @mail($this->to,$this->subject,implode(PHP_EOL,$message),implode(PHP_EOL,$headers)) ? self::ST_SUCCESS : self::ST_FAIL_GENERIC;
		}
		/**
		 * @var array Temporary variable. Do NOT use.
		 */
		protected static $_link_list=array();
		/**
		 * Helper function to replace HTML with a textual derivative (and cleanup the rest).<br>
		 * Most regular expressions came from Jon Abernathy' php class.
		 * @param string $html The original HTML.
		 * @return string The converted text.
		 *
		 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
		 * @author Jon Abernathy <jon@chuggnutt.com>
		 * @copyright Copyright (c) 2005-2007, Jon Abernathy <jon@chuggnutt.com>
		 * @version 1.0.1
		 * @since PHP 4.0.2
		 * @link http://www.chuggnutt.com/html2text-source.php
		 */
		protected static function _htmltotext($html){
			$replace = array( // key=search value=replace
				"/\r/"=>'',                                                             // Non-legal carriage return
				"/[\n\t]+/"=>' ',                                                       // Newlines and tabs
				'/[ ]{2,}/'=>' ',                                                       // Runs of spaces, pre-handling
				'/<script[^>]*>.*?<\/script>/i'=>'',                                    // <script>s -- which strip_tags supposedly has problems with
				'/<style[^>]*>.*?<\/style>/i'=>'',                                      // <style>s -- which strip_tags supposedly has problems with
				'/<h[123][^>]*>(.*?)<\/h[123]>/ie'=>"strtoupper(\"\n\n\\1\n\n\")",      // H1 - H3
				'/<h[456][^>]*>(.*?)<\/h[456]>/ie'=>"ucwords(\"\n\n\\1\n\n\")",         // H4 - H6
				'/<p[^>]*>/i'=>"\n\n\t",                                                // <P>
				'/<br[^>]*>/i'=>"\n",                                                   // <br>
				'/<b[^>]*>(.*?)<\/b>/ie'=>'strtoupper("\\1")',                          // <b>
				'/<strong[^>]*>(.*?)<\/strong>/ie'=>'strtoupper("\\1")',                // <strong>
				'/<i[^>]*>(.*?)<\/i>/i'=>'_\\1_',                                       // <i>
				'/<em[^>]*>(.*?)<\/em>/i'=>'_\\1_',                                     // <em>
				'/(<ul[^>]*>|<\/ul>)/i'=>"\n\n",                                        // <ul> and </ul>
				'/(<ol[^>]*>|<\/ol>)/i'=>"\n\n",                                        // <ol> and </ol>
				'/<li[^>]*>(.*?)<\/li>/i'=>"\t* \\1\n",                                 // <li> and </li>
				'/<li[^>]*>/i'=>"\n\t* ",                                               // <li>
				'/<a [^>]*href="([^"]+)"[^>]*>(.*?)<\/a>/ie'=>'self::_build_link_list("\\1", "\\2")',
																						// <a href="">
				'/<hr[^>]*>/i'=>"\n-------------------------\n",                        // <hr>
				'/(<table[^>]*>|<\/table>)/i'=>"\n\n",                                  // <table> and </table>
				'/(<tr[^>]*>|<\/tr>)/i'=>"\n",                                          // <tr> and </tr>
				'/<td[^>]*>(.*?)<\/td>/i'=>"\t\t\\1\n",                                 // <td> and </td>
				'/<th[^>]*>(.*?)<\/th>/ie'=>"strtoupper(\"\t\t\\1\n\")",                // <th> and </th>
				'/&(nbsp|#160);/i'=>' ',                                                // Non-breaking space
				'/&(quot|rdquo|ldquo|#8220|#8221|#147|#148);/i'=>'"',                   // Double quotes
				'/&(apos|rsquo|lsquo|#8216|#8217);/i'=>"'",                             // Single quotes
				'/&gt;/i'=>'>',                                                         // Greater-than
				'/&lt;/i'=>'<',                                                         // Less-than
				'/&(amp|#38);/i'=>'&',                                                  // Ampersand
				'/&(copy|#169);/i'=>'(c)',                                              // Copyright
				'/&(trade|#8482|#153);/i'=>'(tm)',                                      // Trademark
				'/&(reg|#174);/i'=>'(R)',                                               // Registered
				'/&(mdash|#151|#8212);/i'=>'--',                                        // mdash
				'/&(ndash|minus|#8211|#8722);/i'=>'-',                                  // ndash
				'/&(bull|#149|#8226);/i'=>'*',                                          // Bullet
				'/&(pound|#163);/i'=>'£',                                               // Pound sign
				'/&(euro|#8364);/i'=>'EUR',                                             // Euro sign
				'/&[^&;]+;/i'=>'',                                                      // Unknown/unhandled entities
				'/[ ]{2,}/'=>' '                                                        // Runs of spaces, post-handling
			);
			self::$_link_list = array();
			$html = preg_replace(array_keys($replace),array_values($replace),$html);
			$html = strip_tags($html);
			if(count(self::$_link_list))
				$html.=CRLF.CRLF.'Links:'.CRLF.'------'.CRLF.implode(CRLF,self::$_link_list);
			return $html;
		}
		/**
		 * Helper function used to build a list of links for _htmltotext method.
		 * @param string $link Link URL.
		 * @param string $display Link text.
		 * @return string What to show in place of link.
		 */
		protected static function _build_link_list($link,$display){
			if(substr($link,0,7)=='http://' || substr($link, 0, 8) == 'https://' || substr($link,0,7)=='mailto:'){
				self::$_link_list[]='['.(count(self::$_link_list)+1).'] '.$link;
				$additional=' ['.count(self::$_link_list).']';
			}elseif(substr($link,0,11)=='javascript:'){
				$additional = ''; // ignore javascript link
			}else{
				self::$_link_list[]='['.(count(self::$_link_list)+1).'] http://'
					.$_SERVER['HTTP_HOST'].(substr($link, 0, 1)!='/' ? '/'.$link : $link);
				$additional=' ['.count(self::$_link_list).']';
			}
			return $display.$additional;
		}
	}
	
?>