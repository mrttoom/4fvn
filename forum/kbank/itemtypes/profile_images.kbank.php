<?php
/*======================================================================*\
|| #################################################################### ||
|| # kBank 2.1.2
|| # Coded by mrpaint
|| # Contact: mrpaint@gmail.com
|| # I'm a Vietnamese! Thank you for using this script
|| # Last Updated: 00:45 18-12-2008
|| #################################################################### ||
\*======================================================================*/
if (!class_exists('Item')) {
	trigger_error("Unable to load class: Item", E_USER_ERROR);
} else {
	class profile_images extends Item {
		function profile_images($itemdata) {
			parent::Item($itemdata);
			
			if ($this->itemtypedata['options']['allowAvatar']
				OR $this->itemtypedata['options']['allowAvatarAni']
				OR $this->itemtypedata['options']['maxwidth']
				OR $this->itemtypedata['options']['maxheight']
				OR $this->itemtypedata['options']['maxsize']) {
				$this->isAvatarItem = true;
			}
			if ($this->itemtypedata['options']['allowSigPic']
				OR $this->itemtypedata['options']['allowSigPicAni']
				OR $this->itemtypedata['options']['maxwidthSigPic']
				OR $this->itemtypedata['options']['maxheightSigPic']
				OR $this->itemtypedata['options']['maxsizeSigPic']) {
				$this->isSigPicItem = true;
			}
		}
	
		function getVars_use() {
			return array(
				'allowAvatar' => array(
					'name' => 'Avatar Enabled',
					'desc' => 'Select Yes to overwrite Permission to allow user use avatar',
					'type' => TYPE_BOOL
				),
				'allowAvatarAni' => array(
					'name' => 'Animated Avatar Enabled',
					'desc' => 'Select Yes to overwrite Permission to allow user use animated avatar (.gif)',
					'type' => TYPE_BOOL
				),
				'maxwidth' => array(
					'name' => 'Avatar Max Width',
					'desc' => 'The maximum width of oversized avatar (in pixel)',
					'type' => TYPE_UINT
				),
				'maxheight' => array(
					'name' => 'Avatar Max Height',
					'desc' => 'The maximum height of oversized avatar (in pixel)',
					'type' => TYPE_UINT
				),
				'maxsize' => array(
					'name' => 'Avatar Max Size',
					'desc' => 'The maximum size of oversized avatar (in byte)',
					'type' => TYPE_UINT
				),
				'allowSigPic' => array(
					'name' => 'Signature Picture Enabled',
					'desc' => 'Select Yes to overwrite Permission to allow user use signature picture',
					'type' => TYPE_BOOL
				),
				'allowSigPicAni' => array(
					'name' => 'Animated Signature Picture Enabled',
					'desc' => 'Select Yes to overwrite Permission to allow user use animated signature picture (.gif)',
					'type' => TYPE_BOOL
				),
				'maxwidthSigPic' => array(
					'name' => 'Signature Picture Max Width',
					'desc' => 'The maximum width of oversized avatar (in pixel)',
					'type' => TYPE_UINT
				),
				'maxheightSigPic' => array(
					'name' => 'Signature Picture Max Height',
					'desc' => 'The maximum height of oversized avatar (in pixel)',
					'type' => TYPE_UINT
				),
				'maxsizeSigPic' => array(
					'name' => 'Signature Picture Max Size',
					'desc' => 'The maximum size of oversized avatar (in byte)',
					'type' => TYPE_UINT
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
				KBANK_ACTION_USE => true
			);
		}
		
		function showItem() {
			global $vbulletin,$vbphrase,$itembit_right_column;
			
			$this->getExtraInfo();
					
			return parent::showItem();
		}
		
		function doAction($action) {
			global $vbulletin,$vbphrase,$KBANK_HOOK_NAME;

			if ($action == 'use') {
				if ($this->ready2Enable()) {
					
					$item_new = array(
						'status' => KBANK_ITEM_USED_WAITING,
						'expire_time' => iif($this->data['options']['duration'] > 0,TIMENOW + $this->data['options']['duration']*24*60*60,-1)
					);
					$vbulletin->db->query_write(fetch_query_sql($item_new,'kbank_items',"WHERE itemid = {$this->data[itemid]}"));
					
					if ($this->isAvatarItem AND !$this->isSigPicItem) {
						$vbulletin->url = "profile.php?" . $vbulletin->session->vars['sessionurl'] ."do=editavatar";
					} else if (!$this->isAvatarItem AND $this->isSigPicItem) {
						$vbulletin->url = "profile.php?" . $vbulletin->session->vars['sessionurl'] ."do=editsignature";
					} else {
						$vbulletin->url = "profile.php?" . $vbulletin->session->vars['sessionurl'];
					}
					eval(print_standard_redirect('kbank_use_successful'));
				}
			}
			
			if ($action == 'work'
				//Check for running hook
				&& $KBANK_HOOK_NAME == KBANK_GLOBAL_START
				//Check for better performance
				&& THIS_SCRIPT == 'profile') {
				
				global $permissions;
				
				$this->do_permissions($permissions,$this->itemtypedata['options']);

				//Update for later use (?)
				$vbulletin->userinfo['permissions'] = $permissions;
			}
			
			if ($action == 'work_expired') {
				global $vbphrase,$kbank_profile_images;
				$donow = true;
				$actionStatus = true;
				
				echo "Processing item ID#{$this->data[itemid]}<br/>";
				
				//Search for activating item
				if (!isset($kbank_profile_images[$this->data['userid']])) {
					$kbank_profile_images[$this->data['userid']] = array();
					$itemtypes = $vbulletin->db->query_read("
						SELECT 
							items.itemid AS itemid
							, itemtypes.options AS itemtypeoptions
						FROM `" . TABLE_PREFIX . "kbank_items` AS items
						INNER JOIN `" . TABLE_PREFIX . "kbank_itemtypes` AS itemtypes ON (itemtypes.itemtypeid = items.type)
						WHERE itemtypes.filename = 'profile_images.kbank.php'
							AND items.status = " . KBANK_ITEM_USED_WAITING . "
							AND (items.expire_time > " . TIMENOW . "
								OR items.expire_time < 0)
							AND items.userid = {$this->data['userid']}
					");
					
					while ($itemtype = $vbulletin->db->fetch_array($itemtypes)) {
						$tmp = unserialize($itemtype['itemtypeoptions']);
						foreach ($this->vars as $var) {
							$kbank_profile_images[$this->data['userid']][$var] = max($kbank_profile_images[$this->data['userid']][$var],$tmp[$var]);
						}
					}
				}
				//Check for activating item
				$donow = false;
				foreach ($this->vars as $var) {
					if ($kbank_profile_images[$this->data['userid']][$var] < $this->itemtypedata['options'][$var]) {
						$donow = true;
					}
				}
				if (!$donow) {
					//Found other stuff can handle everything
					echo 'User have other item(s), nothing to do now!<br/>';
				}
				
				$status = array();
				$message = array();
				if ($donow) {
					$owner = fetch_userinfo($this->data['userid']);
					cache_permissions($owner,false);
					//Apply activating options to owner permissions;
					$this->do_permissions($owner['permissions'],$kbank_profile_images[$this->data['userid']]);
					foreach (array('Avatar','SigPic') as $type) {
						//If this item is this type
						eval('$work = iif($this->is' . $type . 'Item,true,false);');
						switch ($type) {
							case 'Avatar':
								$table = 'customavatar';
								$fullname = 'avatar';
								$bits = $vbulletin->bf_ugp_genericpermissions;
								$permkey = 'genericpermissions';
								$canuse = 'canuseavatar';
								$dm = 'Userpic_Avatar';
								break;
							case 'SigPic':
								$table = 'sigpic';
								$fullname = 'sigpic';
								$bits = $vbulletin->bf_ugp_signaturepermissions;
								$permkey = 'signaturepermissions';
								$canuse = 'cansigpic';
								$dm = 'Userpic_Sigpic';
								break;
						}
						if ($work) {
							$removenow = false;
							$updatedone = false;
							$message[$type] = '';
							$status[$type] = 'none';
							//Check if user using system avatar
							if ($type == 'Avatar' AND $owner['avatarid'] <> 0) {
								//Check for System Avatar (only check with type = avatar)
								echo 'User using System Avatar, do nothing!<br/>';
							} else {
								//Check for custom image
								if($customimg = $vbulletin->db->query_first("
									SELECT filedata, dateline, filename, filesize
									FROM `" . TABLE_PREFIX . $table . "`
									WHERE userid = " . intval($owner['userid']) . "
									ORDER BY dateline DESC
									LIMIT 1
								")) {
									$extension = trim(substr(strrchr(strtolower($customimg['filename']), '.'), 1));
									
									$tmp_filename = DIR . "/includes/tmp_profile_images_$customimg[dateline].$extension";
									$tmp_file = fopen($tmp_filename,'w');
									fwrite($tmp_file,$customimg['filedata']);
									fclose($tmp_file);
									
									require_once(DIR . '/includes/class_image.php');
									$image =& vB_Image::fetch_library($vbulletin);
									$imginfo = $image->fetch_image_info($tmp_filename);
									
									if (!($owner['permissions'][$permkey] & $bits[$canuse])) {
										//Check if user can use Avatar/SigPic
										echo "User doesn't have permission to use $type<br/>";
										$removenow = true;
									} else if (
										!($owner['permissions'][$permkey] & $bits['cananimate' .$fullname])
										AND $imginfo['scenes'] > 1
										)
									{
										//gif, we will not process this one! remove now
										echo "GIF image found! Remove now!<br/>";
										$removenow = true;
									} else if ($owner['permissions'][$fullname . 'maxwidth'] < $imginfo[0]
										OR $owner['permissions'][$fullname . 'maxheight'] < $imginfo[1]
										OR $owner['permissions'][$fullname . 'maxsize'] < $customimg['filesize']
									) {
										//Check if current custom image exceed user permission options
										echo "$type need to be updated/removed!<br/>";										
										if ($newimg = $image->fetch_thumbnail(
											basename($tmp_filename)
											, $tmp_filename
											, $owner['permissions'][$fullname . 'maxwidth']
											, $owner['permissions'][$fullname . 'maxheight']
											, $vbulletin->options['thumbquality'])) {
											//Trying to update with smaller size
											echo 'Updating with smaller size! ' . $owner['permissions'][$fullname . 'maxwidth'] . 'x' . $owner['permissions'][$fullname . 'maxheight'] . '<br/>';
											$status[$type] = 'update';
											$data =& datamanager_init($dm, $vbulletin, ERRTYPE_STANDARD, 'userpic');
											
											$data->set('userid', $owner['userid']);
											$data->set('dateline', TIMENOW);
											$data->set('filename', $customimg['filename']);
											$data->set('width', $newimg['width']);
											$data->set('height', $newimg['height']);
											$data->setr('filedata', $newimg['filedata']);
											
											if ($newimg['width'] <= $owner['permissions'][$fullname . 'maxwidth']
												AND $newimg['height'] <= $owner['permissions'][$fullname . 'maxheight']
												AND $newimg['filesize'] <= $owner['permissions'][$fullname . 'maxsize']
												AND $data->save()
											) {
												$updatedone = true;
											} else {
												$removenow = true;
											}
										} else {
											$removenow = true;
										}
									} else {
										echo "$type Size Is Okie, do nothing!<br/>";
									}
									//Send PM
									if ($updatedone) {
										$message[$type] = construct_phrase($vbphrase['kbank_pm_profile_images_message_update'],$newimg['width'],$newimg['height'],$type);
									}
									if ($removenow) {
										//Just remove record
										echo 'Just remove!<br/>';
										$status[$type] = 'remove';
										$vbulletin->db->query_write("
											DELETE FROM `" . TABLE_PREFIX . $table . "`
											WHERE userid = " . intval($owner['userid']) . "
										");
										$message[$type] = construct_phrase($vbphrase['kbank_pm_profile_images_message_remove'],$type);
									}									
									@unlink($tmp_filename);
								} else {
									echo "No Custom $type found, do nothing!<br/>";
								}
							}
						}
					}
					if (isset($message['Avatar']) OR isset($message['SigPic'])) {
						//Send PM
						$from = array(
							'userid' => 1,
							'username' => $vbphrase['kbank'],
							'permissions' => array(
								'pmsendmax' => 5
							)
						);
						$to =& $owner;
						$subject = $vbphrase['kbank_pm_profile_images_subject'];							
						$message = construct_phrase(
							$vbphrase['kbank_pm_profile_images_message']
							, $this->data['name']
							, vbdate($vbulletin->options['dateformat'] . ' ' . $vbulletin->options['timeformat'],$this->data['expire_time'])
							, implode(', ',$message)
							, $vbulletin->options['bburl'] . '/' . $vbulletin->kbank['phpfile'] . '?do=myitems'
							, $vbulletin->options['bburl'] . '/usercp.php'
						);
						$result = kbank_sendPM($from,$to,$subject,$message,false);
					}
				}
				
				$vbulletin->db->query_write("
					UPDATE `" . TABLE_PREFIX . "kbank_items`
					SET status = " . KBANK_ITEM_USED . "
					WHERE itemid = {$this->data['itemid']}
				");
				
				if (count($status) == 2) {
					//Really? Item with both options for Avatar & SigPic. Nothing's impossible!
					return "Avatar: $status[Avatar]; SigPic: $status[SigPic]";
				} else {
					foreach ($status as $tmp) {
						return $tmp;
					}
				};
			}
			
			return parent::doAction($action);
		}
		
		function do_permissions(&$permissions,$options,$check = true) {
			//This is the REAL work function
			global $vbulletin;
			
			//Avatar
			if ($check 
				AND $this->isAvatarItem
				AND !$vbulletin->options['avatarenabled']) {
				//This item is for avatar but Admin has disabled avatar function.... So sad!
				$this->errors[] = $vbphrase['kbank_itemshow_profile_images_allowAvatar_error'];
			}
			//Check for permission $vbulletin->bf_ugp_genericpermissions['canuseavatar']
			if ($options['allowAvatar']
				AND !($permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canuseavatar'])) {
				$permissions['genericpermissions'] = $permissions['genericpermissions'] + $vbulletin->bf_ugp_genericpermissions['canuseavatar'];
			}
			//Check for permission $vbulletin->bf_ugp_genericpermissions['cananimateavatar']
			if ($options['allowAvatarAni']
				AND !($permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['cananimateavatar'])) {
				$permissions['genericpermissions'] = $permissions['genericpermissions'] + $vbulletin->bf_ugp_genericpermissions['cananimateavatar'];
			}
			$permissions['avatarmaxwidth'] = max($permissions['avatarmaxwidth'],$options['maxwidth']);
			$permissions['avatarmaxheight'] = max($permissions['avatarmaxheight'],$options['maxheight']);
			$permissions['avatarmaxsize'] =  max($permissions['avatarmaxsize'],$options['maxsize']);
			
			//Signature Picture
			if ($check
				AND $this->isSigPicItem
				AND !($permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canusesignature'])) {
				//This item is for signature but this member can not use signature! WTF!
				$this->errors[] = $vbphrase['kbank_itemshow_profile_images_allowSigPic_error'];
			}
			//Check for permission $vbulletin->bf_ugp_signaturepermissions['cansigpic']
			if ($options['allowSigPic']
				AND !($permissions['signaturepermissions'] & $vbulletin->bf_ugp_signaturepermissions['cansigpic'])) {
				$permissions['signaturepermissions'] = $permissions['signaturepermissions'] + $vbulletin->bf_ugp_signaturepermissions['cansigpic'];
				//$permissions['sigmaximages'] = max(1,$permissions['sigmaximages']);
			}
			//Check for permission $vbulletin->bf_ugp_signaturepermissions['cananimatesigpic']
			if ($options['allowSigPicAni']
				AND !($permissions['signaturepermissions'] & $vbulletin->bf_ugp_signaturepermissions['cananimatesigpic'])) {
				$permissions['signaturepermissions'] = $permissions['signaturepermissions'] + $vbulletin->bf_ugp_signaturepermissions['cananimatesigpic'];
			}
			$permissions['sigpicmaxwidth'] = max($permissions['sigpicmaxwidth'],$options['maxwidthSigPic']);
			$permissions['sigpicmaxheight'] = max($permissions['sigpicmaxheight'],$options['maxheightSigPic']);
			$permissions['sigpicmaxsize'] =  max($permissions['sigpicmaxsize'],$options['maxsizeSigPic']);
		}
		
		function getItemTypeExtraInfo($itemtypedata) {
			global $vbulletin,$vbphrase; 
			
			$return = array();
			$options = $itemtypedata['options'];
			$newdata = array();
			$permissions = $vbulletin->userinfo['permissions'];

			//Prepare
			//Avatar
			$newdata['allowAvatar'] = ($options['allowAvatar'] AND !($permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canuseavatar']));
			$newdata['allowAvatarAni'] = ($options['allowAvatarAni'] AND !($permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['cananimateavatar']));
			$newdata['maxwidth'] = iif($options['maxwidth'] > $permissions['avatarmaxwidth'],$options['maxwidth'],false);
			$newdata['maxheight'] = iif($options['maxheight'] > $permissions['avatarmaxheight'],$options['maxheight'],false);
			$newdata['maxsize'] = iif($options['maxsize'] > $permissions['avatarmaxsize'],$options['maxsize'],false);
			//Signature Picture
			$newdata['allowSigPic'] = ($options['allowSigPic'] AND !($permissions['signaturepermissions'] & $vbulletin->bf_ugp_signaturepermissions['cansigpic']));
			$newdata['allowSigPicAni'] = ($options['allowSigPicAni'] AND !($permissions['signaturepermissions'] & $vbulletin->bf_ugp_signaturepermissions['cananimatesigpic']));
			$newdata['maxwidthSigPic'] = iif($options['maxwidthSigPic'] > $permissions['sigpicmaxsize'],$options['maxwidthSigPic'],false);
			$newdata['maxheightSigPic'] = iif($options['maxheightSigPic'] > $permissions['sigpicmaxheight'],$options['maxheightSigPic'],false);
			$newdata['maxsizeSigPic'] = iif($options['maxsizeSigPic'] > $permissions['sigpicmaxsize'],$options['maxsizeSigPic'],false);

			//Phrases
			//Avatar
			if ($newdata['allowAvatar']) $return[] = $vbphrase['kbank_itemshow_profile_images_allowAvatar'];
			if ($newdata['allowAvatarAni']) $return[] = $vbphrase['kbank_itemshow_profile_images_allowAvatarAni'];
			if ($newdata['maxwidth']) $return[] = construct_phrase($vbphrase['kbank_itemshow_profile_images_maxwidth'],$newdata['maxwidth']);
			if ($newdata['maxheight']) $return[] = construct_phrase($vbphrase['kbank_itemshow_profile_images_maxheight'],$newdata['maxheight']);
			if ($newdata['maxsize']) $return[] = construct_phrase($vbphrase['kbank_itemshow_profile_images_maxsize'],$newdata['maxsize']);
			//Signature Picture
			if ($newdata['allowSigPic']) $return[] = $vbphrase['kbank_itemshow_profile_images_allowSigPic'];
			if ($newdata['allowSigPicAni']) $return[] = $vbphrase['kbank_itemshow_profile_images_allowSigPicAni'];
			if ($newdata['maxwidthSigPic']) $return[] = construct_phrase($vbphrase['kbank_itemshow_profile_images_maxwidthSigPic'],$newdata['maxwidthSigPic']);
			if ($newdata['maxheightSigPic']) $return[] = construct_phrase($vbphrase['kbank_itemshow_profile_images_maxheightSigPic'],$newdata['maxheightSigPic']);
			if ($newdata['maxsizeSigPic']) $return[] = construct_phrase($vbphrase['kbank_itemshow_profile_images_maxsizeSigPic'],$newdata['maxsizeSigPic']);
			
			if (count($return) == 0) {
				$return[] = $vbphrase['kbank_itemshow_profile_images_none'];
			}
			
			return $return;
		}
	}
}
?>