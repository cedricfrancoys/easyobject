<?php
function_exists('load_class') or die(__FILE__.' requires fc.lib.php');

load_class('utils/XMLParse/XMLTag');


class XMLBrowser {
	private $xml;
	private $tags;
	private $cursor;

	public function __construct($xml) {
		$this->setXml($xml);
		$this->cursor = 0;
		$this->tags = array();
		$this->parse();
	}

	public function getXML() {
		return $this->xml;
	}

	public function setXml($xml) {
		$this->xml = $xml;
	}

	private function parse() {
		preg_match_all("/<([^>\/]+)>/iU", $this->xml, $matches, PREG_OFFSET_CAPTURE);
		for($i = 0, $j = count($matches[1]); $i < $j; ++$i) {
			// extract tag name
			list($tag_name) = explode(' ', $matches[1][$i][0]);
			// find related closing tag (if any)
			$closing_tag = '</'.$tag_name.'>';
			$closing_tag_offset = stripos($this->xml, $closing_tag, $matches[1][$i][1]+strlen($matches[1][$i][0]));
			// if closing tag is not found, we assume it is a standalone tag
			$type = ($closing_tag_offset === false)? XMLTag::SINGLE_TAG : XMLTag::CONTAINER_TAG;
			// fetch tag string (i.e.: tag name + attributes list)
			$tag_string = $matches[1][$i][0];
			/// retreive inner content
			if($type == XMLTag::SINGLE_TAG) $inner_xml = '';
			else $inner_xml = substr($this->xml, $matches[1][$i][1]+strlen($tag_string)+1, $closing_tag_offset-$matches[1][$i][1]-strlen($tag_string)-1);
			// create table of positions (relative to tag offset)
			$positions = array();
			$positions['tag']['start']		= 1;
			$positions['tag']['stop']		= strlen($tag_string);
			$positions['inner']['start']	= $positions['tag']['stop']+2;
			$positions['inner']['stop']		= $positions['inner']['start']+strlen($inner_xml)-1;
			$positions['outer']['start']	= 0;
			$positions['outer']['stop']		= $positions['inner']['stop']+strlen($tag_name)+3;

			$this->tags[] = new XMLTag(
									$this,
									count($this->tags), 	// index
									$matches[1][$i][1]-1, 	// offset
									$type,
									$positions
							);
		}
		$this->reset();
	}

	private function reset() {
		$this->cursor = 0;
	}

	public function hasNext() {
		return ($this->cursor < count($this->tags));
	}

	public function &next() {
		return $this->tags[$this->cursor++];
	}

	public function hasSibling() {
		$result = false;
		if($this->hasNext()) {
			$tag = &$this->tags[$this->cursor];
			// retrieve related closing tag offset
			$closing_offset = $tag->getPosition('outer', 'stop');
			for($i = $this->cursor, $j = $this->getSize(); !$result && $i < $j; ++$i) {
				if($this->tags[$i]->getOffset() > $closing_offset) $result = true;
			}
		}
		return $result;
	}
	
	public function &nextSibling() {	
		$result = null;
		$tag = &$this->tags[$this->cursor];
		// retrieve related closing tag offset
		$closing_offset = $tag->getPosition('outer', 'stop');
		for($i = $this->cursor, $j = $this->getSize(); !$result && $i < $j; ++$i) {
			if($this->tags[$i]->getOffset() > $closing_offset) $result = &$this->tags[$i];
		}
		return $result;	
	}
	
	public function &first() {
		$this->reset();
		return $this->next();
	}


	public function getSize() {
		return count($this->tags);
	}

	public function getTags() {
		return $this->tags;
	}

	public function &getTag($index) {
		$result = null;
		if(isset($this->tags[$index])) $result = &$this->tags[$index];
		return $result;
	}
}