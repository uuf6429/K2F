<?php defined('K2F') or die;

	uses('core/connect.php');

	/**
	 * This REST class works by registering two CRUD classes to handle resources.
	 * These two classes must look like:
	 * <code>
	 *     class CrudObject {
	 *         function load(){}      // Load an instance (property "id" would be set prior to this call).
	 *         function save(){}      // Save instance (created or update).
	 *         function delete(){}    // Deletes an instance (method "load" is called prior to this call).
	 *     }
	 *     class CrudObjects {
	 *         function load($ids){}  // Load instances ($ids is an array of $id used to load instances).
	 *         function save(){}      // Save instances (created or update).
	 *         function delete(){}    // Delete all instances (method "load" is called prior to this call).
	 *         $rows=array();         // The raw array of object we shall be manipulating.
	 *     }
	 *     // NOTE 1: Each method must return whether it was successful or not (true on success, false otherwise).
	 *     // NOTE 2: If any of the above methods do not exist and the REST request explicitly asks for them,
	 *                a response of "501: Not Implemented" is returned.
	 * </code>
	 * <b>These classes look exactly like does implemented in OODB extension,
	 * meaning; you can take example from them, as well as use them!</b>
	 *
	 * @example RestServer Usage Example
	 * <code>
	 *     uses('exts/oodb.php','exts/rest.php');
	 *
	 *     class Product extends DatabaseRow {
	 *         $name='';    // Product name
	 *         $images=[];  // Product images
	 *         public function table(){
	 *             return 'tbl_products';
	 *         }
	 *     }
	 *     class Product extends DatabaseRows {
	 *         public function table(){
	 *             return 'tbl_products';
	 *         }
	 *     }
	 *
	 *     RestServer::register('products','Product','Products');
	 *     RestServer::execute();
	 *
	 * </code>
	 */
	class RestServer {
		/**
		 * @var array List of ObjectItem=>ObjectItems classes, where the former is to load
		 */
		protected static $handlers=array();
		/**
		 * @var array A list of output container names and their respective handlers.
		 * @example
		 *   <code>
		 *     function myHandler($data,$is_multi){
		 *         echo @json_encode($data);
		 *     }
		 *     RestServer::$output_handlers['json']='myHandler';
		 *   </code>
		 */
		public static $output_handlers=array(
			'json' => array('RestServer', '_output_json'),
			'xml'  => array('RestServer', '_output_xml'),
			'html' => array('RestServer', '_output_html'),
		);
		/**
		 * @var string Default output media type (used if none found from request).
		 */
		public static $default_type='html';
		/**
		 * Register a REST resource handler.
		 * @param string $resourceName Resource name (used in URI).
		 * @param string $elementClass CRUD class for handling a single resource (element).
		 * @param string $collectionClass CRUD class for handling multiple resources (collection).
		 */
		public static function register($resourceName,$elementClass,$collectionClass){
			self::$handlers[$resourceName]=array($elementClass,$collectionClass);
		}
		/**
		 * Unregister a resource handler.
		 * @param string $resourceName REST resource name.
		 */
		public static function unregister($resourceName){
			unset(self::$handlers[$resourceName]);
		}
		/**
		 * Attempt to respond to a resource management request.
		 */
		public static function execute(){
			$segments=implode('/',$_SERVER['REQUEST_URI']);
			$id=array_pop($segments); $name=array_pop($segments);
			$verb=strtoupper($_SERVER['REQUEST_METHOD']);
			$multi=($id==''); // whether we handle multiple items, or just one
			if(isset($_SERVER['HTTP_CONTENT_TYPE']))
				$type=strtolower(trim(substr($_SERVER['HTTP_CONTENT_TYPE'],strrpos($_SERVER['HTTP_CONTENT_TYPE'],'/'))));
			if(strpos($name,'.')!==false){
				// handle "users.json"
				list($type,$nm)=explode('.',strrev($name),2);
				$type=strtolower(strrev($type));
			}
			(isset($type) && isset(self::$output_handlers[$type]))
				? $name=strrev($nm) /* $type has been parsed correctly */
				: $type=self::$default_type; /* no $type (use default) */
			if(isset(self::$handlers[$name])){
				$data=$multi
					? new self::$handlers[$name][1]()      // collection class
					: new self::$handlers[$name][0]($id);  // element class
				switch($verb){
					case 'POST':
						// create new item(s)
						$model=get_object_vars(new self::$handlers[$name][1]());
// TODO: INCOMPLETE
						break;
					case 'GET':
						// get item(s)
						$data->load();
						if($multi){
							// get elements from collection class
							$tmp=$data->rows;
							$data=array();
							foreach($tmp as $itm)
								$data[$itm->id]=$itm;
						}
						break;
					case 'PUT':
						// update item(s)
						$_PUT=array();
						// TODO: Heard file_get_contents() might not work every time, double check this.
						$tmp=file_get_contents('php://input');
						$_PUT=parse_str($tmp,$_PUT);
						$model=get_object_vars(new self::$handlers[$name][1]());
						if($multi){
							if(is_array($_PUT['id'])){
								foreach($_PUT['id'] as $n=>$id)
									foreach($model as $key=>$value)
										if($key!='id' && isset($_PUT[$key]))
											$data[$id]->$key=$_PUT[$key][$n];
							}else{
								Connect::status(417); // $_PUT parameters not set (expectation failed)
								die;
							}
						}else{
							foreach($model as $key=>$value)
								if($key!='id' && isset($_PUT[$key]))
									$data->$key=$_PUT[$key];
						}
						$data=$data->save();
						break;
					case 'DELETE':
						// delete item(s)
						$data=$data->delete();
						break;
					default:
						Connect::status(405); // unrecognized REQUEST_METHOD (method not allowed)
						die;
				}
				// output the data
				if(isset(self::$output_handlers[$type])){
					Connect::status(200); // success (ok)
					call_user_func(self::$output_handlers[$type],$data,$multi);
				}else
					Connect::status(500); // unknown output type (internal server error)
			}else
				Connect::status(400); // unknown resource (not implemented)
			die; // done serving request, end here
		}
		protected static function _output_json($data,$is_multi){
			Connect::type('json');
			echo @json_encode($data);
		}
		protected static function _output_xml($data,$is_multi){
			Connect::type('xml');
			$serializer = new XML_Serializer(array(
				XML_SERIALIZER_OPTION_XML_DECL_ENABLED => true,
				XML_SERIALIZER_OPTION_RETURN_RESULT => true
			));
			$data=$serializer->serialize($data);
			Connect::length(strlen($data));
			echo $data;
		}
		protected static function _output_html($data,$is_multi){
			Connect::type('html');
			?><!DOCTYPE html>
			<html dir="ltr" lang="en-US">
				<head>
					<meta charset="UTF-8" />
					<title>REST Explorer</title>
					<meta name="generator" content="REST Explorer 2.0" />
					<script type="text/javascript">
					</script>
					<link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/2.8.1/build/reset/reset-min.css">
					<style type="text/css"> body, table { font: 12px Georgia; color: #333; } </style>
				</head><body><?php
					if($is_multi && is_array($data) && isset($data[0]) && is_object($data[0])){
						echo '<table>';
						$row='<tr><td>'.implode('</td><td>',Security::anohtml(array_keys(get_object_vars($data[0])),false)).'</td></tr>';
						echo '<thead>'.$row.'</thead><tfoot>'.$row.'</tfoot><tbody>';
						foreach($data as $item){
							$cells=Security::anohtml(get_object_vars($item),false);
							if(isset($cells['id']))$cells['id']='<a href="'.Security::snohtml($_SERVER['REQUEST_URI']).$cells['id'].'">'.$cells['id'].'</a>';
							echo '<tr><td>'.implode('</td><td>',$cells).'</td></tr>';
						}
						echo '</tbody></table>';
					}else{
						if(is_scalar($data)){
							echo Security::snohtml(''.$data);
						}else{
							echo '<table><tr><td>'.implode('</td><td>',Security::anohtml(get_object_vars($data),false)).'</td></tr></table>';
						}
					}
				?></body>
			</html><?php
		}
		/**
		 * Apache rules to make REST system work correctly.
		 * @return string Proper htaccess file contents.
		 */
		public static function htaccess(){
			return '# Turn on rewrite engine and redirect broken requests to index
					<IfModule mod_rewrite.c>
						RewriteEngine On
						RewriteCond %{REQUEST_FILENAME} !-l
						RewriteCond %{REQUEST_FILENAME} !-f
						RewriteCond %{REQUEST_FILENAME} !-d
				RewriteRule .* index.php [L,QSA]
				RewriteRule .* - [E=HTTP_CONTENT_TYPE:%{HTTP:Content-Type},L]
					</IfModule>';
		}
	}

?>