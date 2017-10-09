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
	class announce extends Item {
		function getVars_use() {
			return array(
				'text_max' => array(
					'name' => 'Text Max',
					'desc' => 'Enter the maximum characters for announcement',
					'type' => TYPE_UINT
				)
			);
		}
		function getOptions() {
			return array(
				'use_duration' => true,
				'isAnnounce' => true
			);
		}
		function getActions() {
			return array(
				KBANK_ACTION_SWITCH => true
			);
		}
		
		function showItem() {
			global $vbulletin,$vbphrase,$itembit_right_column;
				
			$this->getExtraInfo();
			
			if ($this->data['status'] == KBANK_ITEM_ENABLED) {
				$itembit_right_column .= construct_phrase($vbphrase['kbank_announce_show_link'],$this->data['options']['url'], $vbulletin->kbankBBCodeParser->parse($this->data['options']['text'], 'nonforum'));
			}
			
			return parent::showItem();
		}
		
		function doAction($action) {
			global $kbank,$vbulletin,$bbuserinfo,$permissions,$KBANK_HOOK_NAME;
	
			if ($action == 'enable') {
				$item = $this->data;

				eval('$tmp = "' . fetch_template('kbank_template_announce_enable') . '";');
				eval(standard_error($tmp));
			}
			
			if ($action == 'do_enable') {
				if ($this->ready2Enable()) {
					
					$vbulletin->input->clean_array_gpc('r', array(
						'url' => TYPE_NOHTML,
						'text' => TYPE_NOHTML
						));			
					
					if (strlen($vbulletin->GPC['text']) > $this->itemtypedata['options']['text_max']) {
						$vbulletin->GPC['text'] = substr($vbulletin->GPC['text'],0,$this->itemtypedata['options']['text_max']) . '..';
					}
					$url_cutoff = array(
						'javascript:',
						'ftp://'
					);
					$vbulletin->GPC['url'] = str_replace($url_cutoff,'',$vbulletin->GPC['url']);
					if (substr($vbulletin->GPC['url'],0,7) != 'http://') {
						$vbulletin->GPC['url'] = 'http://' . $vbulletin->GPC['url'];
					}
										
					$item_new = array(
						'status' => KBANK_ITEM_ENABLED,
						'expire_time' => (iif(!$this->data['options']['enabled'],iif($this->data['options']['duration'] > 0,TIMENOW + $this->data['options']['duration']*24*60*60,-1),$this->data['expire_time'])),
						'options' => serialize(array(
							'url' => $vbulletin->GPC['url'],
							'text' => $vbulletin->GPC['text'],
							'enabled' => 1
						))
					);
					$vbulletin->db->query_write(fetch_query_sql($item_new,'kbank_items',"WHERE itemid = {$this->data['itemid']}"));
					
					//Update datastore
					updateAnnounceCache();
				}
			}
			
			if ($this->data['status'] == KBANK_ITEM_ENABLED
				AND ($action == 'sell' OR $action == 'gift')) {
				//Update datastore
				updateAnnounceCache();
			}
			
			if ($action == 'disable') {
				if ($this->ready2Disable()) {
					
					$item_new = array(
						'status' => KBANK_ITEM_AVAILABLE
					);
					$vbulletin->db->query_write(fetch_query_sql($item_new,'kbank_items',"WHERE itemid = {$this->data[itemid]}"));
					
					//Update datastore
					updateAnnounceCache();
				}
			}
			
			if ($action == 'work_real'
				//Check for running hook
				&& $KBANK_HOOK_NAME == KBANK_GLOBAL_START) {
				global $kbank_announces;

				$kbank_announces[] = array(
					'url' => $this->data['options']['url'],
					'text' => $vbulletin->kbankBBCodeParser->parse_bbcode($this->data['options']['text'], true),
					'owner' => getUsername($this->data)
				);
			}
			
			return parent::doAction($action);
		}
	}
}
?>