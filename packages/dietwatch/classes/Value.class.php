<?php

namespace dietwatch {

	class Value extends \core\Object {

		public function getTable() { return 'dietwatch_rel_food_nutrient'; }

		public static function getColumns() {
			return array(
				'NDB_No'		=> array('type' => 'many2one', 'foreign_object' => 'dietwatch\Food'),
				'Nutr_No'		=> array('type' => 'many2one', 'foreign_object' => 'dietwatch\Nutrient'),
				'Nutr_Val'		=> array('type' => 'string'),
				'Units'			=> array('type' => 'related', 'result_type' => 'string',
									'foreign_object' => 'dietwatch\Nutrient',
									'path'	=> array('Nutr_No','Units')
								),
				'nutrient'		=> array('type' => 'related', 'result_type' => 'string',
									'foreign_object' => 'dietwatch\Nutrient',
									'path'	=> array('Nutr_No','NutrDesc')
								)

			);
		}

	}
}