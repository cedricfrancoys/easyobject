<?php

class XMLTag {
	const CONTAINER_TAG = 'container_tag';
	const SINGLE_TAG = 'single_tag';

	private $parser;
	private $index;
	private $offset;
	private $name;
	private $type;
	private $positions;

	public function __construct(&$parser, $index, $offset, $name, $type, $positions) {
		$this->parser		= &$parser;
		$this->index		= $index;
		$this->offset		= $offset;
		$this->name			= $name;		
		$this->type			= $type;
		$this->positions	= $positions;
	}

	function incrementOffset($diff) {
		$this->offset += $diff;
	}

	function getOffset() {
		return $this->offset;	
	}
	
	function getPosition($what, $where) {
		return $this->positions[$what][$where]+$this->offset;
	}

	public function getTagString() {
		return substr($this->parser->getXml(), $this->getPosition('tag','start'), $this->getPosition('tag','stop')-$this->getPosition('tag','start')+1);
	}

	public function getInnerXml() {
		return substr($this->parser->getXml(), $this->getPosition('inner','start'), $this->getPosition('inner','stop')-$this->getPosition('inner','start')+1);
	}

	public function getName() {	
		return $this->name;
	}

	public function getType() {
		return $this->type;
	}

	public function getAttributes() {
		$attributes = array();
		if(preg_match_all('/([a-z-_]+)="([^"]+)"/iU', substr($this->getTagString(), strlen($this->getName())+1), $matches, PREG_SET_ORDER)){
			for($i = 0, $j = count($matches); $i < $j; $i++) $attributes[strtolower($matches[$i][1])] = $matches[$i][2];
		}
		return $attributes;
	}

	public function setAttributes($attributes) {
		$new_tag_string = $this->getName().' ';
		foreach($attributes as $attribute => $value) $new_tag_string .= "$attribute=\"$value\"";
		// get current parser content
		$old_xml = $this->parser->getXml();
		// add everything preceding tag
		$new_xml = substr($old_xml, 0, $this->getPosition('outer', 'start'));
		// add new tag string
		$new_xml .= '<'.$new_tag_string.'>';
		// add inner+closing tag
		$new_xml .= substr($old_xml, $this->getPosition('inner', 'start'));
		// get offset difference
		$diff = strlen($new_tag_string)-strlen($this->getTagString());
		// update positions table
		$this->positions['tag']['stop']		+= $diff;
		$this->positions['inner']['start']	+= $diff;
		$this->positions['inner']['stop']	+= $diff;
		$this->positions['outer']['stop']	+= $diff;

		// update parser content
		$this->parser->setXml($new_xml);
		// update offset for all tags below this one
		for($i = $this->index+1, $j = $this->parser->getSize(); $i < $j; ++$i) {
			$this->parser->getTag($i)->incrementOffset($diff);
		}
	}

	public function setInnerXml($new_inner_xml) {
		// get current parser content
		$old_xml = $this->parser->getXml();
		// add everything preceding tag
		$new_xml = substr($old_xml, 0, $this->getPosition('inner', 'start'));
		// add new tag string
		$new_xml .= $new_inner_xml;
		// add closing tag + trailer
		$new_xml .= substr($old_xml, $this->getPosition('inner', 'stop')+1);
		// get offset difference
		$diff = strlen($new_inner_xml)-strlen($this->getInnerXml());
		// update positions table
		$this->positions['inner']['stop'] += $diff;
		$this->positions['outer']['stop'] += $diff;
		// update parser content
		$this->parser->setXml($new_xml);
		// update offset for all tags below this one
		for($i = $this->index+1, $j = $this->parser->getSize(); $i < $j; ++$i) {
			$this->parser->getTag($i)->incrementOffset($diff);
		}
	}

	public function remove(){
		// get current parser content
		$old_xml = $this->parser->getXml();
		$opening_offset = $this->getPosition('outer', 'start');
		$closing_offset = $this->getPosition('outer', 'stop');
		// add everything preceding tag
		$new_xml = substr($old_xml, 0, $opening_offset);
		// add everything following tag
		$new_xml .= substr($old_xml, $closing_offset+1);
		// get offset difference
		$diff = $opening_offset-$closing_offset-1;
		// update parser content
		$this->parser->setXml($new_xml);
		// update offset for all tags below this one
		for($i = $this->index+1, $j = $this->parser->getSize(); $i < $j; ++$i) {
			$this->parser->getTag($i)->incrementOffset($diff);
		}
	}
	
	public function getParent(){	
		$result = false;
		$opening_offset = $this->getPosition('outer', 'start');
		$closing_offset = $this->getPosition('outer', 'stop');
		// among previsous tags, get the first (going backwards) that starts before and ends after the current one
		for($i = $this->index-1; !$result && $i >= 0; --$i) {
			if(	$opening_offset > $this->parser->getTag($i)->getPosition('outer', 'start') && 
				$closing_offset < $this->parser->getTag($i)->getPosition('outer', 'stop')) 
				$result = $this->parser->getTag($i);
		}
		return $result;	
	}

	// get next sibling
	public function getNextSibling() {	
		$result = false;
		$closing_offset = $this->getPosition('outer', 'stop');
		$parent = $this->getParent();
		if(!$parent) {
			// get the first tag that starts after the current one
			for($i = $this->index+1, $j = $this->parser->getSize(); !$result && $i < $j; ++$i) {
				if($this->parser->getTag($i)->getOffset() > $closing_offset) $result = $this->parser->getTag($i);
			}
		}
		else {
			// get the first tag that starts after the current one and before the parent stops
			for($i = $this->index+1, $j = $parent->getPosition('outer', 'stop'); !$result && $i < $j; ++$i) {
				if(	$this->parser->getTag($i)->getOffset() > $closing_offset &&
					$this->parser->getTag($i)->getPosition('outer', 'stop') < $j)
					$result = $this->parser->getTag($i);
			}			
		}
		return $result;	
	}
	
	public function getFirstChild() {
		$result = false;
		$opening_offset = $this->getPosition('inner', 'start');
		$closing_offset = $this->getPosition('inner', 'stop');		
		// get the first tag that starts after inner and stops before inner ends
		for($i = $this->index+1; !$result && $i < $closing_offset; ++$i) {
			if(	$this->parser->getTag($i)->getPosition('outer', 'start') > $opening_offset &&
				$this->parser->getTag($i)->getPosition('outer', 'stop') < $closing_offset)
				$result = $this->parser->getTag($i);
		}			
		
		return $result;
	}
	
	public function __toString(){
		return '<'.$this->getTagString().'>'.$this->getInnerXml().'</'.$this->getName().'>';
	}
}