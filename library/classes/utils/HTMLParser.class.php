<?php

function_exists('load_class') or die(__FILE__.' requires fc.lib.php');

load_class('utils/Tag');
load_class('utils/XmlIterator');

/**
 * Parser for html contents.
 *
 * @access     public
 * @author	   Cedric Francoys <cedric.francoys@easy-cms.org>
 * @version	   HTMLParser.class, Sun Dec 19 10:46:03 CET 2004
 * @package	   Core
 * @subpackage Parser
 */
class HTMLParser {
	/**
	 * @access private
	 * @var    string
	 */
	var $html;

	/**
	 * @access private
	 * @var    array
	 */
	var $tags_table;

	function HTMLParser($html) {
		$this->html = $html;
	}

	function getHTML() {
		return $this->html;
	}

	function getResult($decorator) {
		$xmlIterator = new XMLIterator($this->html, array('p','div','span','table','tr','td','a'));
		$tag = new Tag();
		return $this->parse($xmlIterator, $tag, $decorator);
	}

	
	function parse(&$xmlIterator, $tag, $decorator) {
		// obtain the current offset
		$offset = $xmlIterator->getOffset();
		// initialize the result string
		$html = '';
		while ($xmlIterator->hasNext()) {
			if(!($newTag = &$xmlIterator->next())) continue;
			$html .= substr($xmlIterator->getXML(), $offset, $newTag->getOffset()-$offset);
			// obtain the new offset (the position following the last read tag)
			$offset = $xmlIterator->getOffset();
			switch($newTag->getType()){
				case SINGLE_TAG :
					$html .= $newTag->getInnerValue();
					break;
				case CONTAINER_TAG :					
					$decorator($newTag);
					$html .= $this->parse($xmlIterator, $newTag, $decorator);
					// obtain the new offset
					$offset = $xmlIterator->getOffset();
					break;
			}
		}
		// add the chars between the last tag and the end of the string
		$html .= substr($xmlIterator->getXML(), $xmlIterator->getOffset());
		return $html;
	}
}