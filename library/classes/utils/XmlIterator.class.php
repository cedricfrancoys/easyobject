<?php
function_exists('load_class') or die(__FILE__.' requires fc.lib.php');

load_class('utils/Tag');

/**
 * Parser for html contents
 *
 * @author	Cedric Francoys <cedric.francoys@easy-cms.org>
 *
 **/

class XmlIterator {
	private $xml;
	private $pos;

	private $hash_table;
	private $tags_table;

	function XMLIterator(&$xml, $tags){
		$this->xml = &$xml;
		$this->reset($tags);
	}

	// Sets internal pointer to the first element
	function reset($tags){
		$this->pos = 0;
		if(!is_array($tags)) $tags = array();
		$this->hash_table = $this->getTags($this->xml, $tags);
	}

	function hasNext(){
		if (($this->hash_table != null) && ($this->pos < count($this->hash_table))) {
			return true;
		}
		return false;
	}

	function &next(){
		$tag_value = &$this->hash_table[$this->pos][0];
		$offset = $this->hash_table[$this->pos][1];
		$name = '';
		$type = '';
		$length = '';
		Tag::getTagInfos($tag_value, $name, $type, $length);
		$tagObject = new Tag($tag_value, $name, $type, $offset, $length);
		$this->tags_table[$this->pos] = &$tagObject;
		++$this->pos;
		return $tagObject;
	}

	function &getXML(){
		return $this->xml;
	}

	function getOffset(){
		if($this->pos > 0 && isset($this->tags_table[$this->pos-1])){
			$tagObject = &$this->tags_table[$this->pos-1];
			if(!is_object($tagObject)) return 0;
			return $tagObject->getOffset()+$tagObject->getLength();
		}
		return 0;
	}

	function getERegTag($tag_names){
		$ereg = "/(((<!\-\-)(.*)(\-\->))";
		foreach($tag_names as $tag_name) {
			$tag_string = '';
			for($i = 0; $i < strlen($tag_name);++$i){
				$tag_string .= "[".strtoupper($tag_name[$i]).strtolower($tag_name[$i])."]";
			}
			$ereg .= "|(((<\s*".$tag_string."[^<]+\/>))|((<\s*".$tag_string.".*>)(.*)(<\s*\/".$tag_string.".*\s*>)))";
		}
		$ereg .= ')/sU';
		return $ereg;
	}

	function getTags($xml, $tag_values_array){
		$result = array();
		$ereg_tag = $this->getERegTag($tag_values_array);

		if (($res = preg_match_all($ereg_tag, $xml, $matches, PREG_OFFSET_CAPTURE))){
			$result =  $matches[0];
		}
		return $result;
	}
}