<?php
function_exists('load_class') or die(__FILE__.' requires fc.lib.php');

load_class('utils/XmlIterator');
load_class('utils/Tag');


/**
 * Parser for html contents.
 *
 * @author	   Cedric Francoys <cedric.francoys@easy-cms.org>
 *
 *
 **/
class HTMLCleaner {
	private $html;
	private $forbidden_tags;
	private $forbidden_attributes;

	private $tagsTable;

	static function clean(&$html, $forbidden_tags=null, $forbidden_attributes=null) {
		$htmlCleaner = new HTMLCleaner($html, $forbidden_tags, $forbidden_attributes);
		$result = &$htmlCleaner->getResult();
		return $result;
	}

	public function HTMLCleaner(&$html, $forbidden_tags=null, $forbidden_attributes=null) {
		$this->html = &$html;
		// note : order in the array is important
		if(is_array($forbidden_tags)) $this->forbidden_tags = $forbidden_tags;
		else $this->forbidden_tags = array('style','meta','link','xml','!--','o');
		if(is_array($forbidden_attributes)) $this->forbidden_attributes = $forbidden_attributes;
		else $this->forbidden_attributes = array('class','style');
	}

	private function &getResult() {
		// code to clean, looking for specified tags
		$xmlIterator = new XmlIterator($this->html, array('style','meta','link','xml','o','p','div','span','table','tr','td','a'));
		$tag = new Tag();
		return $this->parse($xmlIterator, $tag);
	}

	private function &parse(&$xmlIterator, $tag, $evaluate=true, $level=0) {
		// obtain the current offset
		$offset = $xmlIterator->getOffset();
		// initialize the result string
		$result = '';
		while ($xmlIterator->hasNext()) {
			if(!($newTag = &$xmlIterator->next())) continue;
			if($evaluate) $result .= substr($xmlIterator->getXML(), $offset, $newTag->getOffset()-$offset);
			// obtain the new offset (the position following the last read tag)
			$offset = $xmlIterator->getOffset();
			$result .= $newTag->evaluate($this->forbidden_tags, $this->forbidden_attributes);
		}
		// add the chars between the last tag and the end of the string
		$result .= substr($xmlIterator->getXML(), $xmlIterator->getOffset());
		return $result;
	}
}