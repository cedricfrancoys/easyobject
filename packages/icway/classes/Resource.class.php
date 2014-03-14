<?phpnamespace icway {	class Resource extends \core\Object {		public static function getColumns() {			return array(				'title'				=> array('type' => 'string'),				'author'			=> array('type' => 'string'),								'language'			=> array('type'			=> 'selection',											 'selection'	=> array( 'en' => 'English', 'es' => 'Español', 'fr' => 'Français')										),								'filename'			=> array('type' => 'string'),				'description'		=> array('type' => 'short_text'),				'type'				=> array('type' => 'string'),				'size'				=> array('type' => 'string'),				'pages'				=> array('type' => 'integer'),								'content'			=> array('type' => 'binary', 'onchange' => 'icway\Resource::onchange_content'),				'category_id'		=> array('type' => 'many2one', 'foreign_object' => 'icway\Category'),				'category_name'		=> array('type' => 'related', 'result_type' => 'string', 'foreign_object' => 'icway\Category', 'path' => array('category_id','name'))							);		}				public static function onchange_content($om, $uid, $oid, $lang) {			// note : this won't work in client-server mode (since in that case $_FILES array is only available on client-side)			if(isset($_FILES['content'])) {				$om->update($uid, 'icway\Resource', array($oid), 					array(							'filename'	=> $_FILES['content']['name'], 							'size'		=> $_FILES['content']['size'], 							'type'		=> $_FILES['content']['type']						), 					$lang);			}		}					}}