<?php
/*======================================================================*\
|| #################################################################### ||
|| # kBank 2.5.1
|| # Coded by mrpaint
|| # Contact: mrpaint@gmail.com
|| # I'm a Vietnamese! Thank you for using this script
|| # Last Updated: 01:47 05-04-2009
|| #################################################################### ||
\*======================================================================*/
if (!class_exists('Item')) {
	trigger_error("Unable to load class: Item", E_USER_ERROR);
} else {
	class customize_userinfo extends Item {
		function customize_userinfo($itemdata) {
			parent::Item($itemdata);
			
			if ($this->itemtypedata['options']['reveal_username']
				OR $this->itemtypedata['options']['reveal_usertitle']
				OR $this->itemtypedata['options']['reveal_invi']) {
				//Need to be processed as soon as possible
				$this->priority = 10;
			}
			if ($this->itemtypedata['options']['username_smilies'] != 0) {
				//Should be process before normal item
				$this->priority = 6;
			}
		}
	
		/*static*/ function getVars_use() {
			return array(
				'username_max' => array(
					'name' => 'Username Max',
					'desc' => 'Enter the maximum character that member can use in customized username',
					'type' => TYPE_NOHTML
				),
				'username_smilies' => array(
					'name' => 'Username Smilies',
					'desc' => 'Enter the maximum smilies can be used in customized username. Enter "-1" to unlimited',
					'type' => TYPE_INT
				),
				'username_colors' => array(
					'name' => 'Username Colors list',
					'desc' => 'Enter colors that member can choose. Seperate by comma (,)',
					'type' => 'textarea'
				),
				'username_strong' => array(
					'name' => 'Can Use Bold Username?',
					'desc' => 'Select Yes to allow member use <strong>bold</strong> his/her username',
					'type' => TYPE_BOOL
				),
				'usertitle_max' => array(
					'name' => 'Usertitle Max',
					'desc' => 'Enter the maximum character that member can use in customized title',
					'type' => TYPE_NOHTML
				),
				'usertitle_colors' => array(
					'name' => 'Usertitle Colors list',
					'desc' => 'Enter colors that member can choose (for title). Seperate by comma (,)',
					'type' => 'textarea'
				),
				'reveal_username' => array(
					'name' => 'Reveal Other Username',
					'desc' => 'Select Yes to enable Username revealing',
					'type' => TYPE_BOOL
				),
				'reveal_usertitle' => array(
					'name' => 'Reveal Other Usertitle',
					'desc' => 'Select Yes to enable Usertitle revealing',
					'type' => TYPE_BOOL
				),
				'reveal_invi' => array(
					'name' => 'Can See Invisible Member',
					'desc' => 'Select Yes to enable Invisible revealing',
					'type' => TYPE_BOOL
				),
				'edit_time' => array(
					'name' => 'Times To Edit Options',
					'desc' => 'Enter value greater than 0 to limit how many times member can edit options. Enter zero (0) to unlimit',
					'type' => TYPE_INT
				),
			);
		}
		/*static*/ function getOptions() {
			return array(
				'use_duration' => true
			);
		}
		/*static*/ function getActions() {
			return array(
				KBANK_ACTION_SWITCH => true
			);
		}
		
		function showItem() {
			global $vbulletin,$vbphrase,$itembit_right_column;

			$showvalues = false;
			$this->getExtraInfo();
			
			if (!$this->bypassEnableForm()
				AND $this->itemtypedata['options']['edit_time']) {
				if (!$this->canEdit()) {
					$itembit_right_column .= construct_phrase($vbphrase['kbank_itemshow_customize_userinfo_cantedit'],vb_number_format($this->data['options']['edit_time']));
					$showvalues = true;
				} else {
					$itembit_right_column .= construct_phrase($vbphrase['kbank_itemshow_customize_userinfo_canedit'],vb_number_format($this->data['options']['edit_time']),vb_number_format($this->itemtypedata['options']['edit_time']));
				}
			}
			
			if ($this->data['status'] == KBANK_ITEM_ENABLED
				OR $showvalues) {
				
				if ($this->data['userid'] == $vbulletin->userinfo['userid']) {
					$userinfo =& $vbulletin->userinfo;
				} else {
					if (!$vbulletin->userinfo['kbank_granted'][$this->data['userid']]['fetched']) {
						$vbulletin->userinfo['kbank_granted'][$this->data['userid']] = fetch_userinfo($this->data['userid']);
						$vbulletin->userinfo['kbank_granted'][$this->data['userid']]['fetched'] = true;
					}
					$userinfo =& $vbulletin->userinfo['kbank_granted'][$this->data['userid']];
				}
				
				$userinfo['musername'] = null;
				$this->work($userinfo);
				if ($this->data['options']['username']
					OR $this->data['options']['username_color']
					OR $this->data['options']['username_strong']) {
					$itembit_right_column .= construct_phrase($vbphrase['kbank_itemshow_customize_userinfo_username'],$userinfo['musername']);			
				}
				if ($this->data['options']['usertitle']
					OR $this->data['options']['usertitle_color']) {
					$itembit_right_column .= construct_phrase($vbphrase['kbank_itemshow_customize_userinfo_usertitle'],$userinfo['usertitle']);
				}
			}

			
			return parent::showItem();
		}
		
		function getExtraInfo() {
			if ($this->bypassEnableForm()
				OR !$this->canEdit()) {
				$this->itemtype->actions['no_reenable'] = true;
			}
		
			parent::getExtraInfo();
		}
		
		function doAction($action) {
			global $kbank,$vbulletin,$bbuserinfo,$vbphrase,$KBANK_HOOK_NAME;
			
			$vbulletin->kbank['errors'][$this->data['itemid']] = array(); //Reset errors

			if ($action == 'enable') {
				$item = $this->data;
				$username_max = $this->itemtypedata['options']['username_max'];
				$username_colors_options = $this->buildOptions($item['options']['username_color']);				
				$username_strong = $this->itemtypedata['options']['username_strong'];
				$usertitle_max = $this->itemtypedata['options']['usertitle_max'];
				$usertitle_colors_options = $this->buildOptions($item['options']['usertitle_color'],'usertitle_colors','usertitle_color');

				if ($this->bypassEnableForm()
					OR !$this->canEdit()) {
					$action = 'do_enable';
				} else {
					eval('$tmp = "' . fetch_template('kbank_template_customize_userinfo_enable') . '";');
					eval(standard_error($tmp));
				}
			}
			
			if ($action == 'do_enable') {
				if ($this->ready2Enable()) {
					if (!$this->bypassEnableForm()
						AND $this->canEdit()) {
						$vbulletin->input->clean_array_gpc('p', array(
							'itemid'    => TYPE_UINT,
							'username'	=> TYPE_NOHTML,
							'username_color'	=> TYPE_NOHTML,
							'username_strong'	=> TYPE_UINT,
							'usertitle'	=> TYPE_NOTHML,
							'usertitle_color'	=> TYPE_NOHTML,
							'confirm' => TYPE_STR
							));			
						
						if (strlen($vbulletin->GPC['username']) > $this->itemtypedata['options']['username_max']) {
							$vbulletin->GPC['username'] = substr($vbulletin->GPC['username'],0,$this->itemtypedata['options']['username_max']);
						}
						if (strlen($vbulletin->GPC['username']) > 0) {
							//Check for illegal username
							$usernames = explode(',',$vbulletin->options['illegalusernames']);
							$illegal_found = array();
							foreach ($usernames as $username) {
								$username = trim($username);
								if ($username
									AND strpos(strtolower($vbulletin->GPC['username']),strtolower($username)) !== false) {
									$illegal_found[] = trim($username);
								}
							}
							if (count($illegal_found) > 0) {
								//Found something illegal....
								eval(standard_error(construct_phrase($vbphrase['kbank_itemshow_customize_userinfo_username_illegal'],$vbulletin->GPC['username'],implode(', ',$illegal_found))));
							}
							
							//Check for duplicate username
							//Real usernames
							if ($old_found = $vbulletin->db->query_first("
								SELECT userid, username
								FROM `" . TABLE_PREFIX . "user`
								WHERE LOWER(username) = '" . $vbulletin->db->escape_string(strtolower($vbulletin->GPC['username'])) . "'
							")) {
								eval(standard_error(construct_phrase($vbphrase['kbank_itemshow_customize_userinfo_username_duplicate_realusername'],$vbulletin->GPC['username'],getUsername($old_found['userid']))));
							}
							//Our usernames
							$old_found = false;
							$old_userid = 0;
							$old_items = $vbulletin->db->query_read("
								SELECT 
									items.itemid as itemid,
									items.userid as userid,
									items.options as options
								FROM `" . TABLE_PREFIX . "kbank_items` as items
								INNER JOIN `" . TABLE_PREFIX . "kbank_itemtypes` as itemtypes ON (itemtypes.itemtypeid = items.type)
								WHERE itemtypes.filename = 'customize_userinfo.kbank.php'
									AND items.status > " . KBANK_ITEM_AVAILABLE . "
									AND (items.expire_time > " . TIMENOW . "
										OR items.expire_time < 0)
									AND items.itemid <> {$this->data['itemid']}
							");
							while ($old_item = $vbulletin->db->fetch_array($old_items)) {
								$old_item['options'] = unserialize($old_item['options']);
								if (strtolower($old_item['options']['username']) == strtolower($vbulletin->GPC['username'])
									AND $old_item['userid'] != $vbulletin->userinfo['userid']) {
									$old_found = true;
									$old_userid = $old_item['userid'];
									break;
								}
							}
							unset($old_item);
							$vbulletin->db->free_result($old_items);
							if ($old_found) {
								eval(standard_error(construct_phrase($vbphrase['kbank_itemshow_customize_userinfo_username_duplicate'],$vbulletin->GPC['username'],getUsername($old_userid))));
							}
						}
						
						$username_colors = explode(',',$this->itemtypedata['options']['username_colors']);
						if (!count($username_colors) || !in_array($vbulletin->GPC['username_color'],$username_colors)) {
							$vbulletin->GPC['username_color'] = 0;
						}
						if (!$this->itemtypedata['options']['username_strong']) {
							$vbulletin->GPC['username_strong'] = 0;
						}					
						if (strlen($vbulletin->GPC['usertitle']) > $this->itemtypedata['options']['usertitle_max']) {
							$vbulletin->GPC['usertitle'] = substr($vbulletin->GPC['usertitle'],0,$this->itemtypedata['options']['usertitle_max']);
						}
						if (strlen($vbulletin->GPC['usertitle']) > 0) {
							//Check for illegal usertitle
							$usertitles = explode(' ',$vbulletin->options['ctCensorWords']);
							$illegal_found = array();
							foreach ($usertitles as $usertitle) {
								$usertitle = trim($usertitle);
								if ($usertitle
									AND strpos(strtolower($vbulletin->GPC['usertitle']),strtolower($usertitle)) !== false) {
									$illegal_found[] = trim($usertitle);
								}
							}
							if (count($illegal_found) > 0) {
								//Found something illegal....
								eval(standard_error(construct_phrase($vbphrase['kbank_itemshow_customize_userinfo_usertitle_illegal'],$vbulletin->GPC['usertitle'],implode(', ',$illegal_found))));
							}
						}
						$usertitle_colors = explode(',',$this->itemtypedata['options']['usertitle_colors']);
						if (!count($usertitle_colors) || !in_array($vbulletin->GPC['usertitle_color'],$usertitle_colors)) {
							$vbulletin->GPC['usertitle_color'] = 0;
						}
						
						$this->data['options']['username'] = $vbulletin->GPC['username'];
						if ($vbulletin->GPC['username'])
							//cache original username
							$this->data['options']['username_original'] = $vbulletin->userinfo['username'];
						$this->data['options']['username_color'] = $vbulletin->GPC['username_color'];					
						$this->data['options']['username_strong'] = $vbulletin->GPC['username_strong'];					
						$this->data['options']['usertitle'] = $vbulletin->GPC['usertitle'];					
						$this->data['options']['usertitle_color'] = $vbulletin->GPC['usertitle_color'];	
						$this->data['options']['enabled'] = 1;
						$this->data['options']['edit_time']++;
						
						$confirmstr = md5($this->data['itemid'].$vbulletin->userinfo['userid']);
						if ($this->itemtypedata['options']['edit_time'] != 0
							AND $this->data['options']['edit_time'] >= $this->itemtypedata['options']['edit_time']
							AND $vbulletin->GPC['confirm'] != $confirmstr) {
							//This is the last time member can edit options ~> Display confirmation, skip 
							$item =& $this->data;
							$userinfo_bak = $vbulletin->userinfo;
							$newusername = $newusertitle = '';
							$this->work($userinfo_bak);
							if ($this->data['options']['username']
								OR $this->data['options']['username_color']
								OR $this->data['options']['username_strong']) {
								$newusername = construct_phrase($vbphrase['kbank_itemshow_customize_userinfo_username'],$userinfo_bak['musername']);			
							}
							if ($this->data['options']['usertitle']
								OR $this->data['options']['usertitle_color']) {
								$newusertitle = construct_phrase($vbphrase['kbank_itemshow_customize_userinfo_usertitle'],$userinfo_bak['usertitle']);
							}
							eval('$tmp = "' . fetch_template('kbank_template_customize_userinfo_confirm') . '";');
							eval(standard_error($tmp));
						}						
					}
					
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
					
					if ($this->itemtypedata['options']['username_max'] > 0)
					{
						//only store cache if this item allow customizing username
						updateCustomizedUsernameCache();
					}
				}
			}
			
			if ($action == 'disable') {
				if ($this->ready2Disable()) {
					
					$item_new = array(
						'status' => KBANK_ITEM_AVAILABLE
					);
					$vbulletin->db->query_write(fetch_query_sql($item_new,'kbank_items',"WHERE itemid = {$this->data[itemid]}"));
					
					if ($this->itemtypedata['options']['username_max'] > 0)
					{
						//only store cache if this item allow customizing username
						updateCustomizedUsernameCache();
					}
				}
			}
			
			if ($action == 'work'
				AND !$this->skip) {
				//Check for running hook
				switch ($KBANK_HOOK_NAME) {
					case KBANK_GLOBAL_START:
						if ($this->itemtypedata['options']['reveal_invi']) {
							//Invisible Revealing
							if (!($vbulletin->userinfo['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canseehidden'])) {
								global $permissions;
								$permissions['genericpermissions'] = $vbulletin->userinfo['permissions']['genericpermissions'] ^= $vbulletin->bf_ugp_genericpermissions['canseehidden'];
							}
						}
						if ($vbulletin->userinfo['userid'] == $this->data['userid']) {
							global $customize_userinfo_users;
							//Real username Revealing
							if ($this->itemtypedata['options']['reveal_username']) {
								$customize_userinfo_users['disable_username'] = true;
							}
							//Real usertitle Revealing
							if ($this->itemtypedata['options']['reveal_usertitle']) {
								$customize_userinfo_users['disable_usertitle'] = true;
							}
						}
						if ($this->bypassEnableForm()) {
							$this->skip = true;
						}
						break;
					case KBANK_FETCH_MUSERNAME:
						global $kbank_userinfo_tmp;
						$this->work($kbank_userinfo_tmp);
						break;
				}
			}
			
			return parent::doAction($action);
		}
		
		function work(&$user) {
			global $vbulletin,$vbphrase,$customize_userinfo_users;
			
			$do_username = (!$customize_userinfo_users['disable_username']);
			$do_usertitle = (!$customize_userinfo_users['disable_usertitle']);
			$myself = ($vbulletin->userinfo['userid'] == $this->data['userid']);
			
			$tmp_user = &$customize_userinfo_users["{$this->data['userid']}"];
			$options = $this->data['options'];
			$itemtypeoptions = $this->itemtypedata['options'];
			
			$errors =& $vbulletin->kbank['errors'][$this->data['itemid']];
			
			if ($itemtypeoptions['username_smilies']) {
				$tmp_user['options']['smilies'][$this->data['itemid']] = $itemtypeoptions['username_smilies'];
			}
			if ($do_username || $myself) {
				if ($itemtypeoptions['username_max']
					AND $options['username']) {
					$tmp_user['username'] = fetch_censored_text(substr($options['username'],0,$itemtypeoptions['username_max']));
				}
				
				if ($itemtypeoptions['username_colors']
					AND $options['username_color']
					AND in_array($options['username_color'],explode(',',$itemtypeoptions['username_colors']))) {
					$tmp_user['username_style']['color'] = "color: {$options['username_color']}";		
				}
				if ($itemtypeoptions['username_strong']) {
					if ($options['username_strong']) {
						$tmp_user['username_style']['strong'] = "font-weight: bold";		
					} else {
						$tmp_user['username_style']['strong'] = "font-weight: normal";		
					}
				}
				if (count($tmp_user['username_style']) > 0) {
					$prefix = 
						"<span style=\"" . implode('; ',$tmp_user['username_style']) . "\""
						. iif(havePerm($vbulletin->userinfo,KBANK_PERM_ADMIN)," title=\"$user[username]\"")
						. ">";
					$suffix = "</span>";
				}
				
				$username = iif($tmp_user['username'],strip_tags($tmp_user['username']),$user['username']);
				if (!$this->bypassEnableForm()
					AND count($tmp_user['options']['smilies']) > 0
					AND $vbulletin->kbankBBCodeParser) {
					//Parse smilies
					$username_new = $vbulletin->kbankBBCodeParser->parse_smilies($username);
					$smilies_count = substr_count(strtolower($username_new), strtolower('<img'));
					$max_smilies = 0;
					foreach ($tmp_user['options']['smilies'] as $limit) {
						if ($max_smilies <> -1) {
							if ($limit <> -1) {
								$max_smilies += $limit;
							} else {
								$max_smilies = -1;
							}
						}
					}
					if ($max_smilies == -1
						OR $smilies_count <= $max_smilies) {
						$username = $username_new;
					} else {
						$errors[] = construct_phrase($vbphrase['kbank_itemtype_customize_userinfo_username_smilies_error'],$max_smilies,$smilies_count);
					}
				}
				if (!$user['userdisplaygroupid']) {
					$user['customize_userinfo_username'] = $prefix . $username . $suffix;
					$user['musername'] = '';
					$user['musername'] = fetch_musername($user,'displaygroupid','customize_userinfo_username');
				} else {
					global $vbulletin;
					$displaygroupid = $user['userdisplaygroupid'];
					$user['musername'] = 
						$vbulletin->usergroupcache["$displaygroupid"]['opentag'] 
						. $prefix
						. $username
						. $suffix 
						. $vbulletin->usergroupcache["$displaygroupid"]['closetag'];
				}
				//Update `username` entity
				//$user['real_username'] = $user['username'];
				//$user['username'] = $username;
				//skipped!
			}
			
			if ($do_usertitle || $myself) {
				/*if (isset($user['permissions']['genericpermissions'])
					AND !($user['permissions']['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canusecustomtitle'])) {
					$errors[] = $vbphrase['kbank_itemshow_customize_userinfo_usertitle_error'];
				}*/
				if ($itemtypeoptions['usertitle_max']
					AND $options['usertitle']) {
					$tmp_user['usertitle'] = fetch_censored_text(substr(strip_tags($options['usertitle']),0,$itemtypeoptions['usertitle_max']));
				}
				if ($itemtypeoptions['usertitle_colors']
					AND $options['usertitle_color']
					AND in_array($options['usertitle_color'],explode(',',$itemtypeoptions['usertitle_colors']))) {
					$tmp_user['usertitle_style']['color'] = "color: {$options['usertitle_color']}";		
				}
				if (count($tmp_user['usertitle_style']) > 0) {
					$title_prefix = "<span style=\"" . implode('; ',$tmp_user['usertitle_style']) . "\">";
					$title_suffix = "</span>";
				}
				
				$user['usertitle'] = 
					$title_prefix
					. iif($tmp_user['usertitle'],$tmp_user['usertitle'],$user['usertitle'])
					. $title_suffix;
			}
		}
		
		//Support
		/*static*/ function buildOptions($default = false,$varname = 'username_colors',$name = 'username_color') {
			global $vbphrase;
			$options = $this->itemtypedata['options'][$varname];
			if ($options) {
				$options = explode(',',$options);
				if (count($options) > 0) {
					$tmp = '';
					$i = 0;
					foreach ($options as $option) {
						$option = trim($option);
						if ($option)
						{
							$i++;
							$text = $this->buildColorName($option,construct_phrase($vbphrase['kbank_itemshow_color'],$i));
							$tmp .= "<option value=\"$option\" style=\"color: $option\"".($default == $option?' selected="selected"':'').">$text</option>";
						}
					}
					if ($tmp) {
						$tmp = "<option value=\"0\">$vbphrase[please_select_one]</option>" . $tmp;
						$tmp =  "<select name=\"$name\" style=\"width: 100%\">$tmp</select>";
					}
				}
			}
			return $tmp;
		}
		
		/*static*/ function buildColorName($color,$default)
		{
			if (preg_match('/^[a-z]*$/i',$color))
			{
				$str = '';
				$len = strlen($color);
				for ($i = 0; $i < $len; $i++)
				{
					$ord = ord($color[$i]);
					if ($ord >= 65 /*A*/ && $ord <= 90 /*Z*/)
						$str .= ' ';
					$str .= $color[$i];
				}
				return ucwords(trim($str));
			}
			else
				return $default;
		}
		
		/*static*/ function getItemTypeExtraInfo($itemtypedata) {
			global $vbphrase; 
			
			$return = array();
			$options = $itemtypedata['options'];
			
			if ($options['username_max']) $return[] = construct_phrase($vbphrase['kbank_itemtype_customize_userinfo_username_max'],$options['username_max']);
			if ($options['username_smilies'] != 0) $return[] = construct_phrase($vbphrase['kbank_itemtype_customize_userinfo_username_smilies'],iif($options['username_smilies'] != -1," $options[username_smilies]",''));
			$username_colors = explode(',',$options['username_colors']);
			if (count($username_colors) > 1 OR $username_colors[0]) $return[] = construct_phrase($vbphrase['kbank_itemtype_customize_userinfo_username_colors'],count($username_colors));
			if ($options['username_strong']) $return[] = $vbphrase['kbank_itemtype_customize_userinfo_username_strong'];
			if ($options['usertitle_max']) $return[] = construct_phrase($vbphrase['kbank_itemtype_customize_userinfo_usertitle_max'],$options['usertitle_max']);
			$usertitle_colors = explode(',',$options['usertitle_colors']);
			if (count($usertitle_colors) > 1 OR $usertitle_colors[0]) $return[] = construct_phrase($vbphrase['kbank_itemtype_customize_userinfo_usertitle_colors'],count($usertitle_colors));
			if ($options['reveal_username']) $return[] = $vbphrase['kbank_itemtype_customize_userinfo_reveal_username'];
			if ($options['reveal_usertitle']) $return[] = $vbphrase['kbank_itemtype_customize_userinfo_reveal_usertitle'];
			if ($options['reveal_invi']) $return[] = $vbphrase['kbank_itemtype_customize_userinfo_reveal_invi'];
			if ($options['edit_time']) $return[] = construct_phrase($vbphrase['kbank_itemtype_customize_userinfo_edit_time'],vb_number_format($options['edit_time']));
			
			return $return;
		}
		
		function bypassEnableForm() {
			if (!isset($this->needEnableForm)) {
				$this->needEnableForm = (intval($this->itemtypedata['options']['username_max']) > 0
					OR ($this->itemtypedata['options']['username_colors'] AND count(explode(',',$this->itemtypedata['options']['username_colors'])) >0)
					OR $this->itemtypedata['options']['username_strong']
					OR intval($this->itemtypedata['options']['usertitle_max']) > 0
					OR ($this->itemtypedata['options']['usertitle_colors'] AND count(explode(',',$this->itemtypedata['options']['usertitle_colors'])) > 0)
					);
			}
			return !$this->needEnableForm;
		}
		
		function canEdit() {
			return ($this->itemtypedata['options']['edit_time'] == 0
				OR $this->data['options']['edit_time'] < $this->itemtypedata['options']['edit_time']);
		}
	}
	
	function updateCustomizedUsernameCache()
	{
		//store all customized username
		//run when a item with customize username ability is enabled/disabled
		global $vbulletin;
		
		$items_db = $vbulletin->db->query_read("
			SELECT 
				item.*
				,itemtype.options AS itemtypeoptions
			FROM `" . TABLE_PREFIX . "kbank_items` AS item
			INNER JOIN `" . TABLE_PREFIX . "kbank_itemtypes` AS itemtype ON (itemtype.itemtypeid = item.type)
			WHERE itemtype.filename = 'customize_userinfo.kbank.php'
				AND (expire_time > " . TIMENOW . " OR expire_time < 0)
				AND item.status > " . KBANK_ITEM_AVAILABLE . "
		");
		//load all item of "customized_username" has been enabled and not expired
		
		$items = array();
		while ($item = $vbulletin->db->fetch_array($items_db))
		{
			$itemoptions = unserialize($item['options']);
			$itemtypeoptions = unserialize($item['itemtypeoptions']);
			if ($itemtypeoptions['username_max'] > 0 AND $itemoptions['username'] AND $itemoptions['username_original'])
			{
				$items[$itemoptions['username_original']] = array(
					'itemid' => $item['itemid'],
					'username_original' => $itemoptions['username_original'],
					'username' => $itemoptions['username'],
					'expire_time' => $item['expire_time'],
				);
			}
		}
		$vbulletin->db->free_result($items_db);
		unset($item);
		
		updateOurOptions(array('customized_username_cache' => $items));
	}
}
?>