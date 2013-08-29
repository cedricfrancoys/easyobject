<?php

namespace linnetts {

	class Invoice extends \core\Object {

		public static function getColumns() {
			return array(
				'code'			=> array('type' => 'string'),				
				'date'			=> array('type' => 'date'),
				'description'	=> array('type' => 'text'),
				'amount'		=> array(
										'type' => 'function', 
										'store' => true, 
										'result_type' => 'float', 
										'function' => 'linnetts\Invoice::getAmount'
									),
				'vat_rate'		=> array(
										'type' => 'float', 
										'onchange' => 'linnetts\Invoice::onchangeVatRate'
									),				
				'vat'			=> array('type' => 'function', 
										'store' => true, 
										'result_type' => 'float', 
										'function' => 'linnetts\Invoice::getVAT'
									),
				'total'			=> array(
										'type' => 'function', 
										'result_type' => 'float', 
										'function' => 'linnetts\Invoice::getTotal'
									),
				'customer_id'	=> array('type' => 'many2one', 'foreign_object' => 'linnetts\Customer'),
				'jobs_ids'		=> array(
										'type' => 'one2many', 
										'foreign_object' => 'linnetts\Job', 
										'foreign_field' => 'invoice_id', 
										'onchange' => 'linnetts\Invoice::onchangeJobsIds'
									),
				'customer_name'	=> array(
										'type' => 'related', 
										'result_type' => 'string', 
										'foreign_object' => 'linnetts\Customer', 
										'path' => array('customer_id', 'name')
									)
			);
		}

		public static function getDefaults() {
			return array(				
				'code'			=> function() { 
					$code = 0;
					// note: we cannot make calls to ObjectManager here (since we are instanciating a new object)
					$dbConnection = &\DBConnection::getInstance();				
					$res = $dbConnection->getRecords(
						array('linnetts_invoice'),
						array('id'),
						null,
						array(array(array('modifier','>','0'))),
						'id', 'id','desc', 0, 1);
					if($row = $dbConnection->fetchArray($res)) $code = $row['id'];
					return sprintf("%d%05d", date("Y"), $code+1);
				},
				'date'			=> function() { return date("Y-m-d"); },
				'vat_rate'		=> function() { return 0.2; }
			);
		}
			
		public static function getAmount($om, $uid, $oid, $lang) {
			$amount = 0.00;
			// get jobs list
			$res = $om->browse($uid, 'linnetts\Invoice', array($oid), array('jobs_ids'), $lang);
			$jobs_ids = $res[$oid]['jobs_ids'];
			// get jobs values
			$res = $om->browse($uid, 'linnetts\Job', $jobs_ids, array('price'), $lang);
			foreach($jobs_ids as $job_id) $amount += $res[$job_id]['price'];	
			return $amount;
		}

		public static function getVAT($om, $uid, $oid, $lang) {
			$res = $om->browse($uid, 'linnetts\Invoice', array($oid), array('amount', 'vat_rate'), $lang);			
			return $res[$oid]['amount']*$res[$oid]['vat_rate'];
		}		
		
		public static function getTotal($om, $uid, $oid, $lang) {
			$res = $om->browse($uid, 'linnetts\Invoice', array($oid), array('amount', 'vat'), $lang);			
			return $res[$oid]['amount']+$res[$oid]['vat'];
		}
		
		public static function onchangeJobsIds($om, $uid, $oid, $lang) {
			$om->update($uid, 'linnetts\Invoice', array($oid), 
						array(
								'amount' => Invoice::getAmount($om, $uid, $oid, $lang),
								'total' => Invoice::getTotal($om, $uid, $oid, $lang)								
						), $lang);
		}

		// deprecated
		public static function onchangeAmount($om, $uid, $oid, $lang) {
			$om->update($uid, 'linnetts\Invoice', array($oid), 
						array(
								'amount' => Invoice::getAmount($om, $uid, $oid, $lang),
								'total' => Invoice::getTotal($om, $uid, $oid, $lang)								
						), $lang);
		}
		
		public static function onchangeVatRate($om, $uid, $oid, $lang) {
			$om->update($uid, 'linnetts\Invoice', array($oid), 
						array(
								'vat' => Invoice::getVAT($om, $uid, $oid, $lang),
								'total' => Invoice::getTotal($om, $uid, $oid, $lang)								
						), $lang);
		}
		
	}
}