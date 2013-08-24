<?phpnamespace linnetts {	class Job extends \core\Object {		public static function getColumns() {			return array(				'our_ref'			=> array('type' => 'string'),											'title'				=> array('type' => 'string'),				'client_ref'		=> array('type' => 'string'),				'brief'				=> array('type' => 'short_text'),				'pass_by'			=> array('type' => 'string'),				'date'				=> array('type' => 'date'),				'date_aw'			=> array('type' => 'date'),				'date_to_press'		=> array('type' => 'date'),				'date_to_screen'	=> array('type' => 'date'),				'date_to_finishing'	=> array('type' => 'date'),				'deadline'			=> array('type' => 'date'),				// STOCK				'stock'				=> array('type' => 'string'),				'stock_cost'		=> array('type' => 'float', 'onchange' => 'linnetts\Job::onchangePrice'),				// RUN				'run'				=> array('type' => 'string'),				'sheets_mounted'	=> array('type' => 'integer'),				'mounting_cost'		=> array('type' => 'float', 'onchange' => 'linnetts\Job::onchangePrice'),				// REPRO				'nb3_plates'		=> array('type' => 'integer'),				'nb2_plates'		=> array('type' => 'integer'),				'nb1_plates'		=> array('type' => 'integer'),				'plate_cost'		=> array('type' => 'float', 'onchange' => 'linnetts\Job::onchangeTotalRepro'),				'mac_hours'			=> array('type' => 'float'),				'mac_hours_cost'	=> array('type' => 'float', 'onchange' => 'linnetts\Job::onchangeTotalRepro'),				'total_repro'		=> array('type' => 'function', 'result_type' => 'float', 'function' => 'linnetts\Job::getTotalRepro', 'onchange' => 'linnetts\Job::onchangePrice'),								// PRESSROOM				'nb3_cols'			=> array('type' => 'integer'),				'nb2_cols'			=> array('type' => 'integer'),				'nb1_cols'			=> array('type' => 'integer'),				'no_cols_run'		=> array('type' => 'string'),				'cols_cost'			=> array('type' => 'float', 'onchange' => 'linnetts\Job::onchangeTotalPressCost'),				'press_hours'		=> array('type' => 'float', 'onchange' => 'linnetts\Job::onchangeTotalPressCost'),				'press_hours_cost'	=> array('type' => 'float'),				'total_press_cost'	=> array('type' => 'function', 'result_type' => 'float', 'function' => 'linnetts\Job::getTotalPressCost', 'onchange' => 'linnetts\Job::onchangePrice'),				// FINISHING				'fin_1'				=> array('type' => 'string'),				'fin_2'				=> array('type' => 'string'),				'fin_3'				=> array('type' => 'string'),				'fin_4'				=> array('type' => 'string'),				'fin_5'				=> array('type' => 'string'),				'fin_1_cost'		=> array('type' => 'float', 'onchange' => 'linnetts\Job::onchangeFinOurCost'),				'fin_2_cost'		=> array('type' => 'float', 'onchange' => 'linnetts\Job::onchangeFinOurCost'),				'fin_3_cost'		=> array('type' => 'float', 'onchange' => 'linnetts\Job::onchangeFinOurCost'),				'fin_4_cost'		=> array('type' => 'float', 'onchange' => 'linnetts\Job::onchangeFinOurCost'),				'fin_5_cost'		=> array('type' => 'float', 'onchange' => 'linnetts\Job::onchangeFinOurCost'),				'fin_our_cost'		=> array('type' => 'function', 'result_type' => 'float', 'function' => 'linnetts\Job::getFinOurCost'),				'total_fin'			=> array('type' => 'float', 'onchange' => 'linnetts\Job::onchangePrice'),				// SCREENPRINT				'screens'			=> array('type' => 'short_text'),				'n_cols'			=> array('type' => 'string'),				'total_scr_cost'	=> array('type' => 'float', 'onchange' => 'linnetts\Job::onchangePrice'),				// EXTRA				'extra_notes'		=> array('type' => 'short_text'),				'extra_cost'		=> array('type' => 'float', 'onchange' => 'linnetts\Job::onchangePrice'),				// INVOICE				'invoice_text'		=> array('type' => 'short_text'),				// DELIVERY				'delivery'			=> array('type' => 'short_text'),				'delivery_cost'		=> array('type' => 'float', 'onchange' => 'linnetts\Job::onchangePrice'),				// PRICE				'tariff'			=> array('type' => 'integer'),				'price'				=> array('type' => 'function', 'store' => true, 'result_type' => 'float', 'function' => 'linnetts\Job::getPrice'),//				'vat'				=> array('type' => 'float'),				'invoice_id'		=> array('type' => 'many2one', 'foreign_object' => 'linnetts\Invoice'),				'customer_id'		=> array('type' => 'many2one', 'foreign_object' => 'linnetts\Customer', 'onchange' => 'linnetts\Job::onchangeCustomerId'),//				'tasks_ids'			=> array('type' => 'one2many', 'foreign_object'	=> 'linnetts\Task', 'foreign_field' => 'job_id'),				'workdays_ids'	 	=> array('type' => 'many2many', 'foreign_object' => 'linnetts\Workday', 'foreign_field' => 'jobs_ids', 'rel_table' => 'linnetts_rel_workday_job', 'rel_foreign_key' => 'workday_id', 'rel_local_key' => 'job_id'),				'customer_name'		=> array('type' => 'function', 'store' => true, 'result_type' => 'string', 'function' => 'linnetts\Job::getCustomerName')			);		}		public static function getDefaults() {			return array(				'date'				=> function() { return date("Y-m-d"); },				'date_aw'			=> function() { return '0000-00-00'; },				'date_to_press'		=> function() { return '0000-00-00'; },				'date_to_screen'	=> function() { return '0000-00-00'; },				'date_to_finishing'	=> function() { return '0000-00-00'; },				'deadline'			=> function() { return '0000-00-00'; },								'stock_cost'		=> function() { return 0.0; },				'mounting_cost'		=> function() { return 0.0; },				'cols_cost'			=> function() { return 0.0; },				'plate_cost'		=> function() { return 0.0; },				'mac_hours'			=> function() { return 0.0; },				'mac_hours_cost'	=> function() { return 0.0; },								'press_hours'		=> function() { return 0.0; },				'mac_hours_cost'	=> function() { return 0.0; },								'fin_1_cost'		=> function() { return 0.0; },				'fin_2_cost'		=> function() { return 0.0; },				'fin_3_cost'		=> function() { return 0.0; },				'fin_4_cost'		=> function() { return 0.0; },				'fin_5_cost'		=> function() { return 0.0; },				'total_fin'			=> function() { return 0.0; },				'total_scr_cost'	=> function() { return 0.0; },				'extra_cost'		=> function() { return 0.0; },				'delivery_cost'		=> function() { return 0.0; },				'tariff'			=> function() { return 0.0; }			);		}				public static function getCustomerName($om, $uid, $oid, $lang) {			$res = $om->browse($uid, 'linnetts\Job', array($oid), array('customer_id'), $lang);			$customer_id = $res[$oid]['customer_id'];			$res = $om->browse($uid, 'linnetts\Customer', array($customer_id), array('name'), $lang);				return $res[$customer_id]['name'];		}		public static function onchangeCustomerId($om, $uid, $oid, $lang) {			$om->update($uid, 'linnetts\Job', array($oid), array('customer_name' => Job::getCustomerName($om, $uid, $oid, $lang)), $lang);		}		public static function getPrice($om, $uid, $oid, $lang) {			$price = 0.0;			$costs_fields = array('stock_cost', 'mounting_cost', 'total_repro', 'total_press_cost', 'total_fin', 'total_scr_cost', 'extra_cost', 'delivery_cost');			$res = $om->browse($uid, 'linnetts\Job', array($oid), $costs_fields, $lang);			foreach($costs_fields as $cost_field) $price += $res[$oid][$cost_field];			return $price;		}				public static function onchangePrice($om, $uid, $oid, $lang) {			$om->update($uid, 'linnetts\Job', array($oid), array('price' => Job::getPrice($om, $uid, $oid, $lang)), $lang);		}		public static function getFinOurCost($om, $uid, $oid, $lang) {			$price = 0.0;			$costs_fields = array('fin_1_cost', 'fin_2_cost', 'fin_3_cost', 'fin_4_cost', 'fin_5_cost');			$res = $om->browse($uid, 'linnetts\Job', array($oid), $costs_fields, $lang);			foreach($costs_fields as $cost_field) $price += $res[$oid][$cost_field];			return $price;		}		public static function onchangeFinOurCost($om, $uid, $oid, $lang) {			$om->update($uid, 'linnetts\Job', array($oid), array('fin_our_cost' => Job::getFinOurCost($om, $uid, $oid, $lang)), $lang);		}		public static function getTotalPressCost($om, $uid, $oid, $lang) {			$price = 0.0;			$costs_fields = array('cols_cost', 'press_hours_cost');			$res = $om->browse($uid, 'linnetts\Job', array($oid), $costs_fields, $lang);			foreach($costs_fields as $cost_field) $price += $res[$oid][$cost_field];			return $price;		}		public static function onchangeTotalPressCost($om, $uid, $oid, $lang) {			$om->update($uid, 'linnetts\Job', array($oid), array('total_press_cost' => Job::getTotalPressCost($om, $uid, $oid, $lang)), $lang);		}				public static function getTotalRepro($om, $uid, $oid, $lang) {			$price = 0.0;			$costs_fields = array('plate_cost', 'mac_hours_cost');			$res = $om->browse($uid, 'linnetts\Job', array($oid), $costs_fields, $lang);			foreach($costs_fields as $cost_field) $price += $res[$oid][$cost_field];			return $price;		}				public static function onchangeTotalRepro($om, $uid, $oid, $lang) {			$om->update($uid, 'linnetts\Job', array($oid), array('total_repro' => Job::getTotalRepro($om, $uid, $oid, $lang)), $lang);		}	}}