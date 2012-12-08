<?php
/**
 * KNINE php library
 *
 * Illustration class
 *
 */

namespace knine {

	class File extends \core\Object {

		public static function getColumns() {
			return array(
				'name'				=> array('type' => 'string', 'help' => "Complete filename (ex.: 'My image.jpg').\n Important: the name is used in order to determine the content-type."),
				'title'				=> array('type' => 'string', 'help' => 'A title/description about the content of the file.'),
				'content'			=> array('type' => 'binary'),
			);
		}

	}
}