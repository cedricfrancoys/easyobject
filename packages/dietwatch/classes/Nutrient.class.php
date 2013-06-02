<?php

namespace dietwatch {

	class Nutrient extends \core\Object {

		public static function getColumns() {
			return array(
				'NutrDesc'		=> array('label' => 'Description', 'type' => 'string'),
				'Units'			=> array('type' => 'string', 'help' => "g for grams\nmg for miligrams\nµg for micrograms\n"),
				'food_ids'		=> array('type' => 'many2many', 'foreign_object' => 'dietwatch\Food', 'foreign_field' => 'nutrients_ids', 'rel_table' => 'dietwatch_rel_food_nutrient', 'rel_foreign_key' => 'NDB_No', 'rel_local_key' => 'Nutr_No')
			);
		}

	}
}