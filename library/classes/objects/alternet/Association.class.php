<?php

namespace alternet {

	class Association extends \core\Object {

		public static function getColumns() {
			return array(
				'name'				=> array('type' => 'string', 'help' => "Dénomination complète, avec raison sociale le cas échéant"),
				'description'		=> array('type' => 'text', 'help' => "courte présentation (mission, objectifs, moyens, public, vision, ...)"),
				'url'				=> array('type' => 'string'),
				'email'				=> array('type' => 'string'),
				'address'			=> array('type' => 'short_text'),
				'zip'				=> array('type' => 'string', 'help' => "code postal officiel"),
				'country'			=> array('type' => 'selection', 'selection'	=> array('BELGIQUE' => 'Belgique', 'CANADA' => 'Canada', 'ESPAGNE' => 'Espagne', 'FRANCE' => 'France', 'LUXEMBOURG' => 'Luxembourg', 'SUISSE' => 'Suisse')),
				'individuals_ids'	=> array('type' => 'many2many', 'label' =>  'Individuals', 'foreign_object' => 'alternet\Individual', 'foreign_field' => 'associations_ids', 'rel_table' => 'alternet_rel_association_individual', 'rel_foreign_key' => 'individual_id', 'rel_local_key' => 'association_id'),
				'labels_ids'		=> array('type' => 'many2many', 'onchange' => 'alternet\Association::onchange_labels_ids', 'label' =>  'Labels', 'foreign_object' => 'alternet\Label', 'foreign_field' => 'associations_ids', 'rel_table' => 'alternet_rel_association_label', 'rel_foreign_key' => 'label_id', 'rel_local_key' => 'association_id'),
				'labels_txt'		=> array('type' => 'function', 'store' => true, 'result_type' => 'string', 'function' => 'alternet\Association::callable_getLabelsTxt')
			);
		}

		public static function onchange_labels_ids($om, $uid, $oid, $lang) {
			// note : we are in the core namespace, so we don't need to specify it when referring to this class
			$om->update($uid, 'alternet\Association', array($oid), array('labels_txt' => Association::callable_getLabelsTxt($om, $uid, $oid, $lang)), $lang);
		}

		public static function callable_getLabelsTxt($om, $uid, $oid, $lang) {
			$labels_txt = array();
			$res = $om->browse($uid, 'alternet\Association', array($oid), array('labels_ids'), $lang);
			$res2 = $om->browse($uid, 'alternet\Label', $res[$oid]['labels_ids'], array('name'), $lang);
			foreach($res2 as $label_id => $values) $labels_txt[] = $values['name'];
			return implode(', ', $labels_txt);
		}

	}
}