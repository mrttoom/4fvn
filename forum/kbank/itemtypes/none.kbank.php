<?php
/*======================================================================*\
|| #################################################################### ||
|| # kBank 2.1
|| # Coded by mrpaint
|| # Contact: mrpaint@gmail.com
|| # I'm a Vietnamese! Thank you for using this script
|| # Last Updated: 21:55 16-09-2008
|| #################################################################### ||
\*======================================================================*/
if (!class_exists('Item')) {
	trigger_error("Unable to load class: Item", E_USER_ERROR);
} else {
	class none extends Item {
		function getVars_use() {
			return array(
				'info' => array(
					'name' => 'Itemtype Info',
					'desc' => 'Enter info you want to show up<dfn>HTML is enabled<br/>Each line will be assumed as an ability</dfn>',
					'type' => 'textarea',
					'typereal' => TYPE_STR
				),
				'use_duration' => array(
					'name' => 'Itemtype has an Active Duration?',
					'desc' => 'If select No you can leave blank settings about Duration',
					'type' => TYPE_BOOL,
					'default' => 1,
					'extrafunction' => '$itemtype_obj->options["use_duration"] = $value;'
				)
			);
		}
		function getOptions() {
			return array(
				'use_duration' => true,
				'extrafunction' => 'if (!(THIS_SCRIPT == "kbankadmin" AND $_GET["do"] == "settings_update")) $this->options["use_duration"] = isset($this->data["options"]["use_duration"])?$this->data["options"]["use_duration"]:1;'
			);
		}
		function getActions() {
			return array(
				KBANK_ACTION_USE => true
			);
		}
		
		function showItem() {
			global $vbulletin,$vbphrase,$itembit_right_column;
				
			$this->getExtraInfo();
			
			return parent::showItem();
		}
		
		function doAction($action) {
			global $kbank,$vbulletin,$bbuserinfo,$permissions,$KBANK_HOOK_NAME;
			
			if ($action == 'use') {
				if ($this->ready2Enable()) {
					$item_new = array(
						'status' => KBANK_ITEM_USED,
						'expire_time' => iif($this->itemtype->options['use_duration'],iif($this->data['options']['duration'] > 0,TIMENOW + $this->data['options']['duration']*24*60*60,-1),TIMENOW)
					);
					
					$vbulletin->db->query_write(fetch_query_sql($item_new,'kbank_items',"WHERE itemid = {$this->data[itemid]}"));
					
				}
			}
			return parent::doAction($action);
		}
		
		function getItemTypeExtraInfo($itemtypedata) {
			$return = array();
			$options = $itemtypedata['options'];
			
			$return = explode("\r\n",$options['info']);
			
			return $return;
		}
	}
}
?>