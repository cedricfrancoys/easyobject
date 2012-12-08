<?php

class FSManipulator {

	public static function getDirListing($dir_name) {
		if(!is_dir($dir_name)) return null;
		if(($dir = opendir($dir_name)) === false) return null;
		$d_pos = 0;
		$f_pos = 0;
		$list_directoies = array();
		$list_files = array();
		while ($node = readdir($dir)) {
			if ($node != '.' && $node != '..') {
				if (is_dir($dir_name.'/'.$node)) {
					$list_directoies[$d_pos] = $node;
					++$d_pos;
				}
				else{
					$list_files[$f_pos] = $node;
					++$f_pos;
				}
			}
		}
		closedir($dir);
		sort($list_directoies);
		sort($list_files);
		return array_merge($list_directoies, $list_files);
	}

	public function getFileStat($file_name) {
		$fp = fopen($file_name, "r");
		$fstat = fstat($fp);
		fclose($fp);
		return $fstat;
	}

	public function getLastChange($file_name) {
		$fstat = self::getFileStat($file_name);
		return $fstat['mtime'];
	}

	public function getFileContent($file_name) {
		$handle = fopen($file_name, 'rb');
		$content = fread($handle, filesize($file_name));
		fclose($handle);
		return $content;
	}

	public function getTempName($file_name) {
		for($ext = 0;;$ext++) {
			$temp_name = $file_name.sprintf("%03d", $ext);
			if(!is_file($temp_name)) break;
		}
		return $temp_name;
	}

}