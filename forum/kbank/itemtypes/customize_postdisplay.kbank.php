<?php
/*======================================================================*\
|| #################################################################### ||
|| # kBank 2.2.1
|| # Coded by mrpaint
|| # Contact: mrpaint@gmail.com
|| # I'm a Vietnamese! Thank you for using this script
|| # Last Updated: 19:13 30-12-2008
|| #################################################################### ||
\*======================================================================*/
if (!class_exists('Item')) {
	trigger_error("Unable to load class: Item", E_USER_ERROR);
} else {
	class customize_postdisplay extends Item {	
		function getVars_use() {
			return array(
				'postbg' => array(
					'name' => 'Allow Background?',
					'desc' => 'Select Yes to allow Background to be used',
					'type' => TYPE_BOOL
				),
				'postbg_list' => array(
					'name' => 'Background Lists',
					'desc' => 'Enter list of background use can select in format <strong>filepath</strong>:<strong>name</strong>, each background in a new line. Note: <em>filepath</em> must be related to forum root and you can skip <em>name</em>',
					'type' => 'textarea',
					'typereal' => TYPE_STR
				),
				'postbg_url' => array(
					'name' => 'Allow URL Background',
					'desc' => 'Select Yes in order to allow member use external image as Background',
					'type' => TYPE_BOOL
				),
				'postbg_element' => array(
					'name' => 'Element ID to apply Background',
					'desc' => 'Leave blank to use default settings. Note: "###" will be replaced by postid',
					'type' => TYPE_STR,
				),
			);
		}
		function getOptions() {
			return array(
				'use_duration' => true,
				'postbg_positions' => array(
					'no_repeat-top-left' => 'Top Left',
					'no_repeat-top-right' => 'Top Right',
					'no_repeat-bottom-left' => 'Bottom Left',
					'no_repeat-bottom-right' => 'Bottom Right',
					'repeat' => 'Repeat',
					'repeat_x' => 'Repeat Horizontal',
					'repeat_y' => 'Repeat Vertical',
				),
			);
		}
		function getActions() {
			return array(
				KBANK_ACTION_SWITCH => true
			);
		}
		
		function showItem() {
			global $vbulletin,$vbphrase,$itembit_right_column;

			$showvalues = false;
			$this->getExtraInfo();			
			
			if ($this->data['status'] == KBANK_ITEM_ENABLED) {
				if ($this->itemtypedata['options']['postbg'])
				{
					if ($this->data['options']['postbg'])
					{
						$postbg_url = $this->data['options']['postbg'];
						$postbgs = explode("\r\n",$this->itemtypedata['options']['postbg_list']);
						$postbg_name = '';
						foreach ($postbgs as $postbg)
						{
							$tmp = explode(':',$postbg);
							if ($tmp[0] == $postbg_url AND isset($tmp[1])) $postbg_name = $tmp[1];
						}
						if ($postbg_name == '') $postbg_name = basename($postbg_url);
						$postbg_position = $this->itemtype->options['postbg_positions'][$this->data['options']['postbg_position']];
						$itembit_right_column .= construct_phrase($vbphrase['kbank_itemshow_customize_postdisplay_postbg'],$postbg_url,$postbg_name,$postbg_position);
					}
				}
			}

			
			return parent::showItem();
		}
		
		function doAction($action) {
			global $kbank,$vbulletin,$bbuserinfo,$vbphrase,$KBANK_HOOK_NAME;
			
			$vbulletin->kbank['errors'][$this->data['itemid']] = array(); //Reset errors

			if ($action == 'enable') {
				$item =& $this->data;
				$itemtypeoptions =& $this->itemtypedata['options'];
				if ($itemtypeoptions['postbg'])
				{
					//build backgrounds
					$postbg_options = '';
					$postbgs = explode("\r\n",$itemtypeoptions['postbg_list']);
					$isURLBackground = iif($this->data['options']['postbg'] == '',false,true);
					foreach ($postbgs as $postbg)
					{
						if ($postbg)
						{
							$tmp = explode(":",$postbg);
							if (!isset($tmp[1]))
							{
								$tmp[1] = basename($tmp[0]);
							}
							$selected = '';
							if ($this->data['options']['postbg'] == $tmp[0])
							{
								$selected = ' selected="selected"';
								$isURLBackground = false;
							}
							$postbg_options .= "<option value=\"$tmp[0]\"$selected>$tmp[1]</option>";
						}
					}
					//build positions
					$postbg_positions = '';
					foreach ($this->itemtype->options['postbg_positions'] as $value => $name)
					{
						$selected = '';
						if ($this->data['options']['postbg_position'] == $value) $selected = ' selected="selected"';
						$postbg_positions .= "<option value=\"$value\"$selected>$name</option>";
					}
				}

				eval('$tmp = "' . fetch_template('kbank_template_customize_postdisplay_enable') . '";');
				eval(standard_error($tmp,'',false));				
			}
			
			if ($action == 'do_enable') {
				if ($this->ready2Enable()) {
					$vbulletin->input->clean_array_gpc('p', array(
						'itemid'    => TYPE_UINT,
						'postbg_select' => TYPE_STR,
						'postbg_url'	=> TYPE_STR,
						'postbg_position'	=> TYPE_STR,
						));			
						
					if ($this->itemtypedata['options']['postbg'])
					{
						if ($vbulletin->GPC['postbg_select'] != '-1')
						{							
							//background
							if ($vbulletin->GPC['postbg_select'] == '0'
								AND $this->itemtypedata['options']['postbg_url'])
							{
								//Admin allow URL and user selected url mode
								$postbg = $vbulletin->GPC['postbg_url'];
								$pathinfo = pathinfo($postbg);
								if (strpos($postbg,'?')
									OR !in_array(strtolower($pathinfo['extension']),array('jpg','jpeg','png','gif','bmp')))
								{
									$postbg = '';
								}
							}
							else
							{
								$postbg = '';
								$valid_postbgs = explode("\r\n",$this->itemtypedata['options']['postbg_list']);
								foreach ($valid_postbgs as $valid_postbg)
								{
									$tmp = explode(":",$valid_postbg);
									if ($tmp[0] == $vbulletin->GPC['postbg_select']) $postbg = $tmp[0];
								}
							}
							$this->data['options']['postbg'] = $postbg;
							
							//position
							if (isset($this->itemtype->options['postbg_positions'][$vbulletin->GPC['postbg_position']]))
							{
								$this->data['options']['postbg_position'] = $vbulletin->GPC['postbg_position'];
							}
							else
							{
								$keys = array_keys($this->itemtype->options['postbg_positions']);
								$this->data['options']['postbg_position'] = $keys[0];
							}
						}						
						else
						{
							$this->data['options']['postbg'] = '';
							$this->data['options']['postbg_position'] = '';
						}
					}
										
					$this->data['options']['enabled'] = 1;
					$this->data['expire_time'] = iif(
						!$this->data['options']['enabled']
						,iif($this->data['options']['duration'] > 0,TIMENOW + $this->data['options']['duration']*24*60*60,-1)
						,$this->data['expire_time']);
						
					//Optimizing....
					$options = array();
					foreach ($this->data['options'] as $key => $val) {
						if ($val) {
							$options[$key] = $val;
						}
					}
					
					$item_new = array(
						'status' => KBANK_ITEM_ENABLED,
						'expire_time' => $this->data['expire_time'],
						'options' => serialize($options)
					);

					$vbulletin->db->query_write(fetch_query_sql($item_new,'kbank_items',"WHERE itemid = {$this->data[itemid]}"));
				}
			}
			
			if ($action == 'disable') {
				if ($this->ready2Disable()) {
					
					$item_new = array(
						'status' => KBANK_ITEM_AVAILABLE
					);
					$vbulletin->db->query_write(fetch_query_sql($item_new,'kbank_items',"WHERE itemid = {$this->data[itemid]}"));
				}
			}
			
			if ($action == 'work') {
				//Check for running hook
				switch ($KBANK_HOOK_NAME) {
					case KBANK_POSTBIT_COMPLETE:
						if (THIS_SCRIPT == 'showthread' OR THIS_SCRIPT == 'showpost')
						{
							//currently only work with showthread and showpost
							$options = $this->data['options'];
							$itemtypeoptions = $this->itemtypedata['options'];
							global $post,$stylevar,$kbank_active_items;
							
							if ($itemtypeoptions['postbg'])
							{
								$postbg_position = str_replace(array('-','_'),array(' ','-'),$options['postbg_position']);
								$background = "background: $stylevar[alt1_bgcolor] url($options[postbg]) $postbg_position;";
								$GLOBALS['customize_postdisplay_cache'][$post['postid']]['background'] = $background;
							}
							
							$foundOther = false;
							$foundThis = false;

							foreach ($GLOBALS['kbank_active_items'] as $userid => $useritems)
							{
								if ($foundOther) break;
								foreach ($useritems as $item)
								{
									if ($foundTher) break;
									if (is_subclass_of($item,'Item'))
									{
										if ($item->data['itemid'] == $this->data['itemid']) $foundThis = true;
										if ($foundThis AND $item->itemtype->data['filename'] == substr(strrchr(__FILE__, DIRECTORY_SEPARATOR), 1))
										{
											$foundOTher = true;
											break;
										}
									}
								}
							}
							if (!$foundOther)
							{
								$css = '';
								foreach ($GLOBALS['customize_postdisplay_cache'] as $postid => $cache)
								{
									if ($itemtypeoptions['postbg_element'])
									{
										$elementid = str_replace('###',$postid,$itemtypeoptions['postbg_element']);
									}
									else
									{
										$elementid = "post_message_$postid";
									}
									$css .= "#$elementid {\r\n";
									foreach ($cache as $element)
									{
										$css .= $element . "\r\n";
									}
									$css .= "}\r\n";
									$GLOBALS['customize_postdisplay_cache'] = array();
								}
								if ($css != '') {
									$css = "<style type=\"text/css\">\r\n$css</style>\r\n";
									$GLOBALS['headinclude'] .= "<!-- CSS automatically added by " . substr(strrchr(__FILE__, DIRECTORY_SEPARATOR), 1) . " at line " . __LINE__ . " -->\n" . $css;
								}
							}
						}
						break;
				}
			}
			
			return parent::doAction($action);
		}
		
		//Support
		function getItemTypeExtraInfo($itemtypedata) {
			global $vbphrase; 
			
			$return = array();
			$options = $itemtypedata['options'];

			if ($options['postbg']) $return[] = $vbphrase['kbank_itemtype_customize_postdisplay_postbg'];
			
			return $return;
		}
		
		function validateSettings($settings)
		{
			if ($settings['postbg']
				&& !(count($postbgs) > 0)
				&& !$settings['postbg_url'])
			{
				print_stop_message('kbank_itemtype_customize_postdisplay_postbg_invalid');
			}
		}
	}
}
?>