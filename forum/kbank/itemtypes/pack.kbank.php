<?php
/*======================================================================*\
|| #################################################################### ||
|| # kBank 1.9.6 (Optimizing)
|| # Coded by mrpaint
|| # Contact: mrpaint@gmail.com
|| # I'm a Vietnamese! Thank you for using this script
|| # Last Updated: 12:02 09-07-2008
|| #################################################################### ||
\*======================================================================*/
if (!class_exists('Item')) {
	trigger_error("Unable to load class: Item", E_USER_ERROR);
} else {
	class pack extends Item {
		function getVars_use() {
			return array(
				'itemtypeids' => array(
					'name' => 'Itemtype(s)',
					'desc' => 'ItemTypeID(s) of including itemtype(s)<br/><em>Separate by comma</em>',
					'type' => TYPE_STR
				)
			);
		}
		function getOptions() {
			return array(
				'use_duration' => true
			);
		}
		function getActions() {
			return array(
				KBANK_ACTION_USE => true,
				KBANK_ACTION_USE_CUSTOMNAME => 'kbank_unpack'
			);
		}
				
		function doAction($action) {
			if ($action == 'use') {
				global $vbulletin;
				
				$itemtypeids = explode(',',$this->itemtype->data['options']['itemtypeids']);
				$newitemids = array();
				
				if (count($itemtypeids)) {
					foreach ($itemtypeids as $itemtypeid) {
						$itemtypes[] = newItemType($itemtypeid);				
					}
					
					foreach ($itemtypes as $itemtype_obj) {
						if ($itemtype_obj) {
							$itemtype = $itemtype_obj->data;
							$itemoptions = array();
							if ($itemtype_obj->options['use_duration']) {
								$itemoptions['duration'] = $this->data['options']['duration'];
							}
							$item_new = array(
								'type' => $itemtype['itemtypeid'],
								'name' => "$itemtype[name]",
								'description' => $vbulletin->db->escape_string($this->data['description']),
								'price' => $this->data['price'],
								'userid' => $vbulletin->userinfo['userid'],
								'creator' => $vbulletin->userinfo['userid'],
								'create_time' => TIMENOW,
								'expire_time' => $this->data['expire_time'],
								'status' => KBANK_ITEM_AVAILABLE,
								'options' => serialize($itemoptions)
							);
							
							$vbulletin->db->query_write(fetch_query_sql($item_new,'kbank_items'));
							$newitemids[] = $vbulletin->db->insert_id();
						}
					}
				}
				
				$item_new = array(
					'status' => KBANK_ITEM_USED,
					'expire_time' => TIMENOW
				);
				
				$vbulletin->db->query_write(fetch_query_sql($item_new,'kbank_items',"WHERE itemid = {$this->data[itemid]}"));
				
				if (count($newitemids)) {
					$itemid = $newitemids[count($newitemids) - 1]; //get the last new itemid
					$vbulletin->url = $vbulletin->kbank['phpfile'] . '?' . $vbulletin->session->vars['sessionurl'] ."do=myitems&itemid=$itemid#item$itemid";
				}
			}
			
			return parent::doAction($action);
		}
		
		function getItemTypeExtraInfo($itemtypedata) {
			global $vbphrase; 
			
			$return = array();
			$itemtypes = array();
			$itemtypeids = explode(',',$itemtypedata['options']['itemtypeids']);
			
			if (count($itemtypeids)) {
				foreach ($itemtypeids as $itemtypeid) {
					if (!isset($itemtypes[$itemtypeid])) {
						$itemtypes[$itemtypeid] = array(
							'itemtype' => newItemType($itemtypeid),
							'count' => 1
						);
					} else {
						$itemtypes[$itemtypeid]['count']++;
					}
				}
				
				foreach ($itemtypes as $itemtypeid => $info) {
					$itemtype_obj =& $info['itemtype'];
					if ($itemtype_obj) {
						$itemtype_obj->getExtraInfo();
						$itemtype = $itemtype_obj->data;
						$return[] = 
							"<strong>$itemtype[name]</strong>"
							. iif($info['count'] > 1," x<strong style=\"color:red;\">$info[count]</strong>")
							. iif($itemtype['options_processed_list'],"<ul>$itemtype[options_processed_list]</ul>");
					}
				}
			}
			return $return;
		}
	}
}
?>