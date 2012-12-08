<?php
function_exists('load_class') or die(__FILE__.' requires fc.lib.php');

load_class('utils/HtmlCleaner');

define('CONTAINER_TAG',	'container_tag');
define('SINGLE_TAG',	'single_tag');


/**
 * Tag Class
 *
 * Tag element
 *
 * @access     public
 * @author	   Cedric Francoys <cedric.francoys@easy-cms.org>

 */
class Tag {
	private $name;
	private $type;
	private $offset;
	private $length;

	private $tag_string;

	private $attributes_table;

	/**
	 * Constructor
	 *
	 * @access   public
 	 * @param    string  The complete tag syntax (i.e. : <tagname attr1="value1" attr2="value2">...</tagname>, <br />, etc)
 	 * @param    integer The position of the tag in the document
	 */
	function Tag($tag_string='', $name='', $type='', $offset=0, $length=0) {
		$this->tag_string = $tag_string;
		$this->name = $name;
		$this->type = $type;
		$this->offset = $offset;
		$this->length = $length;

		$this->attributes_table = array();

		$matches = array();
		$end = strpos($this->tag_string, '>');
		$tag_string = substr($tag_string, 0, $end);
		if(preg_match_all('`([a-zA-Z-_]+)="([^"]+)"`', $tag_string, $matches, PREG_SET_ORDER)){
			$matches_count = count($matches);
			for($i = 0; $i < $matches_count; $i++){
				$attr_name  = $matches[$i][1];
				$attr_value = $matches[$i][2];
				$this->attributes_table[strtolower($attr_name)] = $attr_value;
			}
		}
	}

	function getOpeningTag($forbidden_attributes=null) {
		if(!is_array($forbidden_attributes)) $forbidden_attributes = array();
		$result = '<'.$this->name.' ';
		foreach($this->attributes_table as $attribute => $value) {
			if(!in_array($attribute, $forbidden_attributes)) $result .= $attribute.'="'.$value.'" ';
		}
		$result .= '>';
		return $result;
	}

	function getClosingTag() {
		$result = '';
		if($this->type == CONTAINER_TAG) $result = '</'.$this->name.'>';
		return $result;
	}

	function getInnerValue($forbidden_tags=null) {
		$result = '';
		if(!is_array($forbidden_tags)) $forbidden_tags = array();
		if($this->type == CONTAINER_TAG) {
			$begin = strpos($this->tag_string, '>') + 1;
			$end = stripos($this->tag_string, '</'.$this->name);
			$inner_length = $end-$begin;
			$inner_string = substr($this->tag_string, $begin, $inner_length);
			$result = HtmlCleaner::clean($inner_string);
		}
		return $result;
	}

	/**
	 * Performs actions specific to the tag
	 *
	 * @access   public
	 * @abstract
 	 * @return   mixed Returns a bool in case of a conditional tag, a string otherwise (html output)
	 */
	function evaluate($forbidden_tags=null, $forbidden_attributes=null) {
		$result = '';
		if(!is_array($forbidden_tags)) $forbidden_tags = array();
		if(!is_array($forbidden_attributes)) $forbidden_attributes = array();
		if(!in_array($this->name, $forbidden_tags)) {
			$result .= $this->getOpeningTag($forbidden_attributes);
			$result .= $this->getInnerValue($forbidden_tags);
			$result .= $this->getClosingTag();
		}
		return $result;
	}

	/**
	* Offset getter
	*
	* @access   public
 	* @return   string The offset of the tag
	*/
	function getOffset(){
		return $this->offset;
	}

	/**
	* Length getter
	*
	* @access   public
 	* @return   string The length of the tag
	*/
	function getLength(){
		return $this->length;
	}

	/**
	* Obtain tag infos
	*
	* @access   public
	* @static
 	* @param    string  The tag found
 	* @param    string  A reference to a string to store the name value.
 	* @param    string  A reference to a string to store the type value.
 	* @return   void
	*/
	public static function getTagInfos($tag, &$name, &$type, &$length){
		$length = strlen($tag);
		$type = CONTAINER_TAG;
		for($begin = 1; $begin < strlen($tag) && $tag[$begin] == ' '; ++$begin){}
		for($end = $begin; $end < strlen($tag) && !in_array($tag[$end], array(' ',':','[','>')); ++$end){}
		if($tag[strlen($tag)-2] == '/')	$type = SINGLE_TAG;
		$name = substr($tag, $begin, $end-$begin);
	}

}