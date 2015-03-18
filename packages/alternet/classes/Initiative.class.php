<?php

namespace alternet {

	class Initiative extends \core\Object {

		public static function getColumns() {
			return array(
				'name'				=> array('type' => 'string', 'help' => "Dénomination complète, avec raison sociale le cas échéant"),
				'description'		=> array('type' => 'short_text', 'help' => "courte présentation (mission, objectifs, moyens, public, vision, ...)"),
				'url'				=> array('type' => 'string'),
				'email'				=> array('type' => 'string'),
				'address'			=> array('type' => 'short_text'),
				'zip'				=> array('type' => 'string', 'help' => "code postal officiel"),
				'country'			=> array('type' => 'selection', 'selection'	=> array('BELGIQUE' => 'Belgique', 'CANADA' => 'Canada', 'ESPAGNE' => 'Espagne', 'FRANCE' => 'France', 'LUXEMBOURG' => 'Luxembourg', 'SUISSE' => 'Suisse')),
				'labels_ids'		=> array('type' => 'many2many', 'label' => 'Labels', 'foreign_object' => 'alternet\Label', 'foreign_field' => 'initiatives_ids', 'rel_table' => 'alternet_rel_initiative_label', 'rel_foreign_key' => 'label_id', 'rel_local_key' => 'inititative_id'),
			);
		}

	}
}
