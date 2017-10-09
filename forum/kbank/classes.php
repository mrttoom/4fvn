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

class ArrayClass {	
	//parent class for our classes
	function destroy() {
		$this->data = array();
		if ($this->itemtype) {
			unset($this->itemtype);
		}
	}
}

class ItemType extends ArrayClass {
	function ItemType($itemtypeid, $itemtypedata, $force2create = false) {
		//create itemtype
		$this->data = $itemtypedata;
		if (!is_array($this->data['options'])) {
			$this->data['options'] = unserialize($this->data['options']);
		}
		
		if (!$force2create
			AND $this->data['options']['deleted']) {
			$this->deleted = true;
		}
		
		$this->includeItemType();
	}
	
	function includeItemType() {
		//load script
		if ($this->deleted) return false;
		$this->data['fileaddress'] = DIR . '/kbank/itemtypes/' . $this->data['filename'];
		if (file_exists($this->data['fileaddress'])
			AND !is_dir($this->data['fileaddress'])) {
			include_once($this->data['fileaddress']);
			$this->classname = basename($this->data['filename'],'.kbank.php');
			
			eval('$this->vars_use = ' . $this->classname . '::getVars_use();');
			eval('$this->options = ' . $this->classname . '::getOptions();');
			eval('$this->actions = ' . $this->classname . '::getActions();');
			
			if ($this->options['extrafunction']) {
				eval($this->options['extrafunction']);
			}
		} else {
			return false;
		}
	}
	
	function getExtraInfo() {
		//name tells everything!
		if ($this->deleted) return false;
	
		global $vbulletin,$vbphrase;
		
		if ($this->options['getExtraInfo']) return true;
		
		$this->data['price_str'] = vb_number_format($this->data['price'],$vbulletin->kbank['roundup']) . " {$vbulletin->kbank['name']}";
		if ($this->options['use_duration']) {
			$this->data['duration_price_str'] = construct_phrase($vbphrase['kbank_itemtype_duration_price'],iif($this->data['options']['duration_price'],vb_number_format($this->data['options']['duration_price'],$vbulletin->kbank['roundup']),$vbphrase['kbank_not_updated']),$vbulletin->kbank['ItemDurationStep'],$vbulletin->kbank['name']);
		}
		
		//build manufactures list
		if ($manufactures = explodeUserid($this->data['userid'])) {	
			$manufactures_a = array();
			foreach ($manufactures as $manufacture) {
				if ($manufacture > 0
					AND !isset($manufactures_a[$manufacture])) {
					$manufactures_a[$manufacture] = getUsername($manufacture);
				}
			}
			$this->data['manufactures'] = implode(', ',$manufactures_a);
			$this->data['manufactureids'] = array_keys($manufactures_a);
			$this->data['manufactureids_str'] = implode(',',$this->data['manufactureids']);
		}
		
		//build abilities list
		if ($this->classname) {
			eval('$this->data[\'options_processed\'] = ' . $this->classname . '::getItemTypeExtraInfo($this->data);');
			if (count($this->data['options_processed'])) {
				$this->data['options_processed_list'] = '';
				foreach ($this->data['options_processed'] as $option) {
					$this->data['options_processed_list'] .= "<li>$option</li>";
				}
			}
		}
		
		if (!$this->data['options']['image']) {
			$this->data['shortinfo'] = "<strong>{$this->data[name]}</strong><br/><dfn>{$this->data[description]}</dfn>";
		} else {
			$this->data['shortinfo'] = "<strong>{$this->data[name]}</strong><br/><img src=\"{$vbulletin->options['bburl']}/{$this->data['options']['image']}\"><br/><dfn>{$this->data[description]}</dfn>";
		}
		
		$this->options['getExtraInfo'] = true;
	}
	
	function validateSettings($settings)
	{
		$result = true;
		if ($this->classname) {
			eval('$result = ' . $this->classname . '::validateSettings($settings);');
		}
		return $result;
	}
}

class Item extends ArrayClass {
	function Item($itemdata) {	
		//create item
		global $vbulletin;
		
		$this->data = $itemdata;
		if (!is_array($this->data['options'])) {
			$this->data['options'] = unserialize($this->data['options']);
		}
		
		//load itemtype
		if (!$this->itemtype = newItemType($this->data['type'])) {
			trigger_error("Unable to load ItemType #{$this->data['type']}}", E_USER_ERROR);
		}
		
		$this->itemtypedata = &$this->itemtype->data;
		if ($this->itemtype->options['use_duration']
			AND !$this->data['options']['duration']) {
			$this->data['options']['duration'] = $vbulletin->kbank['ItemDurationStep']; //manually adjust duration if missing
		}
		
		//Setup variable for later use
		$this->vars = array_keys($this->getVars_use());
		$this->errors = ''; //Contain errors message
		$this->skip = false;
		$this->priority = 5; //Default priority for each item is 5
	}
	
	function showItem() {
		//show item (my items)
		global $kbank,$vbulletin,$vbphrase,$itembit_right_column;
		
		$this->getExtraInfo();
		$this->buildErrors();
		
		//show selling status
		if ($this->data['status'] == KBANK_ITEM_SELLING) {
			global $vbphrase;
			if ($this->data['receivers']) {
				$itembit_right_column .= $this->data['receivers'] . '<br/>';
			}
			$itembit_right_column .= construct_phrase($vbphrase['kbank_selling_price'],$this->data['price']) . '<br/>';
		}
		
		$item = $this->data;
		$itemtype = $this->itemtypedata;
		$itemtype['actions'] = $this->itemtype->actions;
		
		if ($item['userid'] != $vbulletin->userinfo['userid']) {
			$item['tr_class'] = 'alt2';
			$itembit_right_column = construct_phrase($vbphrase['kbank_item_user'],$item['username']) . '<br/>' . $itembit_right_column;
		}

		eval('$tmp = "' . fetch_template('kbank_itembit') . '";');
		return $tmp;
	}
	
	function showItemActivated() {
		//show item activated (my items)
		global $vbulletin,$vbphrase;
		$this->getExtraInfo();
		
		$item = $this->data;
		$status = $vbphrase['kbank_item_activated'];

		eval('$tmp = "' . fetch_template('kbank_itembit_simple') . '";');
		return $tmp;
	}
	
	function getExtraInfoReceiver() {
		//build receiver
		global $vbphrase;
		
		if (count($this->data['options']['receiver'])) {
			$receivers = array();
			foreach ($this->data['options']['receiver'] as $receiverid) {
				$receivers[] = getUsername($receiverid);
			}
			$this->data['receivers'] = construct_phrase($vbphrase['kbank_receivers_are'],implode(iif($vbphrase['kbank_receivers_or'],$vbphrase['kbank_receivers_or'],', '),$receivers));
		}
	}
	
	function getExtraInfoExpire() {
		//build expire time
		global $vbulletin,$vbphrase;
		
		if ($this->data['expire_time'] > 0) {
			$this->data['exp'] = vbdate(
				$vbulletin->options['dateformat']
				. iif($this->data['status'] == KBANK_ITEM_BIDDING,' ' . $vbulletin->options['timeformat'])
				, $this->data['expire_time']);
		} else {
			$this->data['exp'] = $vbphrase['kbank_never'];
		}
		$this->data['exp_str'] = 
			iif(
				$this->data['status'] != KBANK_ITEM_BIDDING
				,$vbphrase['kbank_exp']
				,$vbphrase['kbank_exp_bid']
			)
			. ': '
			. $this->data['exp'];
	}
	
	function getExtraInfo() {
		//build a lot of info
		//AdminCP (Item Man) & MyItems
		global $vbulletin,$vbphrase;
		
		if ($this->options['getExtraInfo']) return true;
		
		if ($this->itemtype->options['use_duration'] 
			AND !$this->data['options']['duration']) {
			$this->data['options']['duration'] = $vbulletin->kbank['ItemDurationStep'];
		}
		if ($this->itemtype->actions[KBANK_ACTION_USE_CUSTOMNAME]) {
			$this->data['kbank_use'] = $vbphrase[$this->itemtype->actions[KBANK_ACTION_USE_CUSTOMNAME]];
		}
		if ($this->going2Expire()) {
			$this->data['warning'] = true;
			$this->data['errors'] .= "$vbphrase[kbank_item_expire_soon]<br/>";
		}
		
		$this->getExtraInfoReceiver();
		$this->getExtraInfoExpire();
		
		$this->data['type_name'] = $this->itemtypedata['name'];
		
		$this->data['price_str'] = vb_number_format($this->data['price'],$vbulletin->kbank['roundup']) . " {$vbulletin->kbank['name']}";
		$this->data['duration_str'] = iif($this->itemtype->options['use_duration'],construct_phrase($vbphrase['kbank_duration_is'],iif($this->data['options']['duration'] > 0,$this->data['options']['duration'],$vbphrase['kbank_forever'])));
		if ($this->data['tax']) {
			//There will be `tax` in some cases (kShop with non-Company items)
			$this->data['tax_str'] = vb_number_format($this->data['tax'],$vbulletin->kbank['roundup']) . " {$vbulletin->kbank['name']}";
		}
		$this->data['seller'] = getUsername($this->data['userid'],$vbulletin->options['bburl'] . '/' . $vbulletin->kbank['phpfile'] . '?do=shop&username=','username');
		
		//Bids
		if ($this->data['status'] == KBANK_ITEM_BIDDING
			AND $vbulletin->kbank['maxLastBids'] <> 0
			AND is_array($this->data['options']['bids']) //only show to kBank Admin
		) {
			$this->data['bids_list'] = array();
			$keys = array_reverse(array_keys($this->data['options']['bids']));
			$count = 0;
			
			foreach ($keys as $key) {
				if ($vbulletin->kbank['maxLastBids'] == -1
					OR $count < $vbulletin->kbank['maxLastBids']) {
					$record = $this->data['options']['bids'][$key];
					
					if (havePerm($vbulletin->userinfo,$record)
						OR havePerm($vbulletin->userinfo,KBANK_PERM_ADMIN)) {
						$this->data['bids_list'][] = 
							construct_phrase(
								$vbphrase['kbank_bids_bit']
								,getUsername($record)
								,vb_number_format($record['bid'],$vbulletin->kbank['roundup'])
								,$vbulletin->kbank['name']
								,vbdate(
									$vbulletin->options['dateformat']
									. ' ' . $vbulletin->options['timeformat']
									,$record['bid_time'])
							);
						$count++;
					}
				}
			}
			
			$this->data['bids_list_count'] = count($this->data['bids_list']);
			$this->data['bids_list'] = implode('</li><li>',$this->data['bids_list']);
		}
		$this->doAction('bid_expired'); //Just run it, all check included!
		//Bids - complete!
				
		$this->data['shortinfo'] = 
			iif($this->itemtypedata['options']['image'],'<image src="' . $this->itemtypedata['options']['image'] . '" alt="' . $this->data['type_name'] . '" align=left style="margin-right: 5px">')
			. "<strong>{$this->data[name]}</strong><br/>"
			. "$vbphrase[kbank_item_type]: {$this->data[type_name]}<br/>"
			. "{$this->data[exp_str]}<br/>"
			. iif($this->data['duration_str'],"{$this->data['duration_str']}<br/>")
			. "<dfn>{$this->data[description]}</dfn>";
		
		$this->options['getExtraInfo'] = true;
	}
	
	function getShopInfo() {
		//build info to show on shop
		global $vbulletin,$vbphrase;
		$this->getExtraInfo();
		$this->itemtype->getExtraInfo();
		
		//Manufacture
		$this->data['itemtype_'] = $this->itemtypedata[''];		
		$this->data['itemtype_manufactures'] = array();
		if (count($this->data['itemtype_'])) {
			foreach ($this->data['itemtype_'] as $userid) {
				$this->data['itemtype_manufactures'][] = getUsername($userid,$vbulletin->kbank['phpfile'] . '?do=shop&username=','username');
			}
			$this->data['itemtype_manufactures'] = implode(', ',$this->data['itemtype_manufactures']);
		}
		//Ability
		$this->data['itemtype_options_list'] = $this->itemtypedata['options_processed_list'];
		
		//Multi-items 
		if ($this->data['count']) {
			$this->data['count_str'] = vb_number_format($this->data['count']);
			$this->data['name'] = preg_replace('/^(.*) \d*$/','\1',$this->data['name']);
		}
		
		$this->data['shopinfo'] =
			iif($this->itemtypedata['options']['image'],'<image src="' . $this->itemtypedata['options']['image'] . '" alt="' . $this->data['type_name'] . '" align=left style="margin-right: 5px">')
			. "<strong>{$this->data[name]}</strong>" 
			. iif($this->itemtype->actions[KBANK_ACTION_SWITCH] AND $this->data['options']['enabled'],' <em style="color: red">'.$vbphrase['kbank_item_enabled'].'</em>')
			. iif($this->data['status'] == KBANK_ITEM_SELLING_UNLIMIT AND $this->data['options']['sold_counter'] > 0,' <em>(' . construct_phrase($vbphrase['kbank_sold_counter'],vb_number_format($this->data['options']['sold_counter'])) . ')</em>')
			. '<br/>'
			. iif($this->data['receivers'],$this->data['receivers'] . '<br/>')			
			. "$vbphrase[kbank_item_type]: {$this->data[type_name]} (<a href=\"#\" onClick=\"kshop_itemtypeinfo_toggle({$this->data['itemid']}); return false;\">$vbphrase[kbank_detail]</a>)<br/>"			
			. "{$this->data[exp_str]}<br/>"
			. "<dfn>{$this->data[description]}</dfn>"
			. "<div id=\"kbank_itemtypeinfo_itemid{$this->data['itemid']}\" style=\"display: none\">"
			. iif($this->data['duration_str'],"{$this->data['duration_str']}<br/>")
			. iif($this->data['itemtype_manufactures'],construct_phrase($vbphrase['kbank_manufactures_are'],$this->data['itemtype_manufactures']) . '<br/>')
			. iif($this->data['itemtype_options_list'],construct_phrase($vbphrase['kbank_options_are'],$this->data['itemtype_options_list']) . '<br/>')
			. iif($this->data['bids_list'],construct_phrase($vbphrase['kbank_bids_are'],$this->data['bids_list'],$this->data['bids_list_count']) . '<br/>')
			. '</div>';
	}
	
	function doAction($action) {
		global $vbulletin,$vbphrase,$userinfo;
		//to be override
		
		if (!$userinfo) $userinfo =& $vbulletin->userinfo;
		
		if ($action == 'buy') {
			if ($olditem = $vbulletin->db->query_first("
					SELECT *
					FROM `" . TABLE_PREFIX . "kbank_items`
					WHERE type = {$this->data['type']}
						AND userid = {$userinfo['userid']}
						AND (status > " . KBANK_ITEM_AVAILABLE . "
							OR status = " . KBANK_ITEM_DELETED . ")
					ORDER BY create_time DESC
					LIMIT 1
				")) {
				//Search for an old item with same itemtype have been used/enabled or even deleted

				$options = unserialize($olditem['options']);
				if (is_array($options)) {
					foreach ($options as $key => $value) {
						if (!in_array($key,array('duration','bids','expire_time_bidding','approved','edit_time'))
							AND !isset($this->data['options'][$key])) {
							$this->data['options'][$key] = $value;
						}
					}
				}
				$this->data['options']['enabled'] = null;
			}
			if (isset($this->data['options']['sold_counter'])) {
				$this->data['options']['sold_counter'] = null; //clear sold counter
			}
			if (isset($this->data['options']['receiver'])) {
				$this->data['options']['receiver'] = null; //clear receiver
			}
			if (is_array($this->data['options'])) {
				$this->data['options'] = serialize($this->data['options']);
			}
			$this->data['status'] = KBANK_ITEM_AVAILABLE; //IMPORTANT!
			$this->data['userid'] = $userinfo['userid']; //IMPORTANT!
		}
		
		if ($action == 'bid') {
			//This action change database directly

			//Permission checking
			if ($this->data['status'] != KBANK_ITEM_BIDDING
				OR !havePerm($userinfo,KBANK_PERM_COMPANY,true)
				OR $this->data['expire_time'] < TIMENOW) {
				return KBANK_ERROR_NO_PERM;
			}
			
			$bid = $vbulletin->GPC['bid'][$this->data['itemid']];
			$highestBid = $this->highestBid();
			if ($bid <= $this->data['price']) {
				//User place bid lower than what we have got
				if ($bid == $this->data['price']
					AND !count($highestBid)) {
					//if this is the first, he/she can bid with amount of default bid
				} else {
					return fetch_error('kbank_item_bid_lower',vb_number_format($this->data['price'],$vbulletin->kbank['roundup']),$vbulletin->kbank['name']);
				}
			}
			if (count($highestBid)
				AND $bid - $highestBid['bid'] < $vbulletin->kbank['bidStep']) {
				return fetch_error('kbank_item_bid_step',vb_number_format($highestBid['bid'],$vbulletin->kbank['roundup']),vb_number_format($vbulletin->kbank['bidStep'],$vbulletin->kbank['roundup']),$vbulletin->kbank['name']);
			}
			
			if (!isset($this->data['options']['bids'])) {
				$this->data['options']['bids'] = array();
			}
			
			//Calculating bidding-fee
			$paid = 0;
			$fee = calcTransferTax($bid,$vbulletin->kbank['ItemBidFee']);
			foreach ($this->data['options']['bids'] as $record) {
				if ($record['userid'] == $userinfo['userid']) {
					$paid += $record['paid'];
				}
			}
			$need2paid = $fee - $paid;

			$result = transferMoney(
				//sender userid
				$userinfo['userid']
				//receiver userid
				,$this->data['userid']
				//amount of money
				,$need2paid
				//comment - support array
				,'bid_' . $this->data['itemid']
				//amount inhand - "null" to by pass validation
				,$userinfo[$vbulletin->kbank['field']]
				//boolean value: log donation or not
				,true
				//boolean value: auto send pm or not
				,false
				//tax rate - "false" to use default donation tax
				,KBANK_NO_TAX
				//boolean value: output or just return error message
				,false
				//postid
				,0
				//queries to run - array('from','to','banklogs_itemname')
				,array('banklogs_itemname' => iif($this->data['userid'] == 0,'items','other'))
			);
			
			if ($result !== true) {
				return $result;
			}
			
			$this->data['options']['bids'][] = array(
				'userid' => $userinfo['userid'],
				'username' => $userinfo['username'],
				'bid' => $bid,
				'bid_time' => TIMENOW,
				'paid' => $need2paid
			);
			
			$item_new = array(
				'price' => $bid,
				'options' => serialize($this->data['options'])
			);
			
			//Do database change
			$vbulletin->db->query_write(fetch_query_sql($item_new,'kbank_items',"WHERE itemid = {$this->data[itemid]}"));
			
			//updateWarningItem(); - done by main script
			
			return true;
		}
		
		if ($action == 'bid_expired') {	
			//Bidding expired
			
			//Permission check - for safe
			if ($this->data['status'] == KBANK_ITEM_BIDDING
				AND $this->data['expire_time'] < TIMENOW //Bidding expire time passed
				) {
				if (
					($vbulletin->kbank['BidWinnerBuyAfter'] === false //there is no limitation
					OR TIMENOW < $this->data['expire_time'] + $vbulletin->kbank['BidWinnerBuyAfter'])
					AND count($this->highestBid())) {
					//If there is a highest bid we will specified receiver!
					$this->data['options']['receiver'] = array($this->highestBid());
				}
				$this->data['status'] = KBANK_ITEM_SELLING;
				if ($this->data['options']['expire_time_bidding'] > 0) {
					$this->data['expire_time'] = $this->data['expire_time'] + $this->data['options']['expire_time_bidding'];
				} else {
					$this->data['expire_time'] = $this->data['options']['expire_time_bidding'];
				}
				//We have to update old info
				$this->getExtraInfoReceiver();
				$this->getExtraInfoExpire();
			} else {
				return false;
			}
		}
		
		if ($action == 'approve') {
			//This action change database directly
			$kBankAdmin =& $vbulletin->userinfo;
		
			//One more permission check - just for safe
			if (THIS_SCRIPT != 'kbankadmin'
				OR !havePerm($kBankAdmin,KBANK_PERM_ADMIN)
				OR $this->data['status'] != KBANK_ITEM_PENDING) {
				print_stop_message('kbank_no_permission');
			}
			
			if (isset($this->data['options']['approved'][$kBankAdmin['userid']])) {
				$do_approved = false;
			} else {
				$do_approved = true;
				$this->data['options']['approved'][$kBankAdmin['userid']] = $kBankAdmin['username'];
			}
			
			$approved = array();
			foreach ($this->data['options']['approved'] as $userid => $username) {
				if (in_array($userid,$vbulletin->kbank['AdminIDs'])
					AND !in_array($userid,$approved)) {
					$approved[] = $userid;
				}
			}
			
			if (count($approved) >= $vbulletin->kbank['requestApproval']
				OR count($approved) == count($vbulletin->kbank['AdminIDs'])) {
				//Great! Approved
				$item_new = array(
					'status' => $this->data['options']['status_pending'],
					'options' => serialize($this->data['options'])
				);
			} else if ($do_approved) {
				//Okay but we need more
				$item_new = array(
					'options' => serialize($this->data['options'])
				);
			}
			
			if ($item_new) {
				//Do database change
				$vbulletin->db->query_write(fetch_query_sql($item_new,'kbank_items',"WHERE itemid = {$this->data[itemid]}"));
			}
			
			logkBankAction(
				'admin_item_approve',
				$this->data['itemid']
			);
			
			updateWarningItem();
			
			define('CP_REDIRECT','kbankadmin.php?do=item_man');
			print_stop_message('kbank_item_approved');
		}
		
		return true;
	}
	
	//Supporter
	function ready2Enable() {
		global $vbulletin,$userinfo;
		if (!$userinfo) $userinfo =& $vbulletin->userinfo;
		return (havePerm($userinfo,$this->data)
			AND in_array($this->data['status'],array(KBANK_ITEM_SELLING,KBANK_ITEM_AVAILABLE,KBANK_ITEM_ENABLED))
			AND ($this->data['expire_time'] > TIMENOW
				OR $this->data['expire_time'] < 0));
	}
	
	function ready2Disable() {
		global $vbulletin,$userinfo;
		if (!$userinfo) $userinfo =& $vbulletin->userinfo;
		return (havePerm($userinfo,$this->data) 
			AND in_array($this->data['status'],array(KBANK_ITEM_ENABLED))
			AND ($this->data['expire_time'] > TIMENOW
				OR $this->data['expire_time'] < 0));
	}
	
	function canBuy($userid) {
		if (!is_array($this->data['options']['receiver'])
			OR !count($this->data['options']['receiver'])) {
			//No receiver specified!
			return true;
		}
		foreach ($this->data['options']['receiver'] as $receiver) {
			if (
				(is_numeric($receiver) AND $userid == $receiver) //receiver is userid
				OR (is_array($receiver) AND $userid == $receiver['userid']) //receiver is userinfo
			) {
				return true;
			}
		}
		return false;
	}
	
	function getRealPrice() {
		//calculating item real price based on itemtype price and duration price
		global $vbulletin;
		if ($this->itemtype->options['use_duration']) {
			if ($this->data['options']['duration'] > 0) {
				return ($this->itemtypedata['price'] + $this->data['options']['duration']/$vbulletin->kbank['ItemDurationStep']*$this->itemtypedata['options']['duration_price']);
			} else {
				return $this->itemtypedata['options']['duration_price_forever'];
			}
		} else {
			return $this->itemtypedata['price'];
		}
	}
	
	function getStatus($separator = ' ') {
		global $vbulletin,$vbphrase;
		
		$result = array();
		
		if ($this->data['expire_time'] < TIMENOW
			AND $this->data['expire_time'] > 0) {
			return $vbphrase['kbank_item_expired'];
			//Expired! Return immediately
		}
		switch ($this->data['status']) {
			case KBANK_ITEM_PENDING:
				$result[] = $vbphrase['kbank_item_pending'];
				if (THIS_SCRIPT == 'kbankadmin') {
					$count = 0;
					foreach ($this->data['options']['approved'] as $userid => $username) {
						$result[] = construct_phrase($vbphrase['kbank_item_pending_approved'],$username);
						$count++;
					}
					$result[] = construct_phrase($vbphrase['kbank_item_pending_require'],$vbulletin->kbank['requestApproval'] - $count);
				}
				break;
			case KBANK_ITEM_DELETED:
				$result[] = $vbphrase['kbank_item_deleted'];
				break;
			case KBANK_ITEM_BIDDING:
				$result[] = $vbphrase['kbank_item_bidding'];
				break;
			case KBANK_ITEM_SELLING_UNLIMIT:
				$result[] = $vbphrase['kbank_item_selling_unlimit'];
				break;
			case KBANK_ITEM_SELLING:
				$result[] = $vbphrase['kbank_item_selling'];
				break;
			case KBANK_ITEM_AVAILABLE:
				$result[] = $vbphrase['kbank_item_available'];
				break;
			case KBANK_ITEM_ENABLED:
				$result[] = $vbphrase['kbank_item_enabled'];
				break;
			case KBANK_ITEM_USED:
			case KBANK_ITEM_USED_WAITING:
				$result[] = $vbphrase['kbank_item_used'];
				break;
		}
		if (!in_array($this->data['status'],array(KBANK_ITEM_DELETED))
			AND $this->going2Expire()) {
			$result[] = $vbphrase['kbank_item_expire_soon'];
		}
		
		return implode($separator,$result);
	}
	
	function going2Expire() {	
		global $vbulletin;
		if ($this->data['expire_time'] < 0) {
			return false;
		}
		if ($this->data['expire_time'] - max(1,$vbulletin->kbank['MonthlyTaxReminder'])*24*60*60 < TIMENOW) {
			return true;
		}
	}
	
	function highestBid() {
		//get the highest bid has been place
		if (!$this->highestBid) {
			if (is_array($this->data['options']['bids'])) {
				foreach ($this->data['options']['bids'] as $record) {
					if (bid_cmp($record,$this->highestBid) > 0) {
						$this->highestBid = $record;
					}
				}
			}
		}
		return $this->highestBid; //return (from cached)
	}
	
	function buildErrors() {
		global $vbulletin,$vbphrase;
		
		$errors =& $vbulletin->kbank['errors'][$this->data['itemid']];

		$this->data['errors'] = '';
		if (is_array($errors)
			AND count($errors) > 0) {
			foreach ($errors as $error) {
				$this->data['errors'] .= "<strong>$vbphrase[kbank_item_errors]</strong>: $error<br/>";
			}
		}
	}
	
	/*static*/ function getItemTypeExtraInfo($itemtypedata) {
	}
	
	function validateSettings($settings)
	{
		return true;
	}
}

function newItemType($itemtypeid,$itemtypedata = false,$force2create = false) {	
	global $vbulletin,$kbank_itemtypes;
	
	if (!$vbulletin->kbank['itemEnabled']
		AND VB_AREA <> 'AdminCP') {
		return false;
	}
	
	$itemtypeids = array();
	$itemtypedatas = array();
	if (is_array($itemtypeid)) {	
		//Many itemtypeids
		foreach ($itemtypeid as $id) {
			if (is_numeric($id)
				AND $id > 0
				AND !isset($kbank_itemtypes[$id])
				AND !in_array($id,$itemtypeids)) {
				$itemtypeids[] = $id;
			}
		}
		if (count($itemtypeids)) {
			foreach ($itemtypeids as $itemtypeid) {
				$itemtypedatas[$itemtypeid] = $vbulletin->kbank_itemtypes[$itemtypeid];
			}
		}
	} else if(is_numeric($itemtypeid)
		AND $itemtypeid > 0) {
		//Only 1 itemtypeid
		if (!isset($kbank_itemtypes[$itemtypeid])) {
			//It has not been initialized, we can do it!
			$itemtypeids[] = $itemtypeid;
			
			if ($itemtypedata) {
				$itemtypedatas[$itemtypeid] = $itemtypedata;
			} else {
				$itemtypedatas[$itemtypeid] = $vbulletin->kbank_itemtypes[$itemtypeid];

				if (!$itemtypedatas[$itemtypeid]) {
					return false;
				}
			}
		} else {
			//Nothing else to do
			$itemtype =& $kbank_itemtypes[$itemtypeid];
		}
	}
	
	if (count($itemtypeids) > 0
		AND count($itemtypeids) == count($itemtypedatas)) {
		foreach ($itemtypeids as $itemtypeid) {
			$itemtypedata = $itemtypedatas[$itemtypeid];
			$kbank_itemtypes[$itemtypeid] =& new ItemType($itemtypeid,$itemtypedata,$force2create);
		}
		
		if (count($itemtypeids) == 1) {
			$itemtype =& $kbank_itemtypes[$itemtypeids[0]];
		}
	}
	
	if ($itemtype) {
		return $itemtype;
	} else {
		return false;
	}
}

function newItem($itemid,$itemdata = false) {
	global $kbank_items,$vbulletin;
	if (!$vbulletin->kbank['itemEnabled']
		AND VB_AREA <> 'AdminCP') {
		return false;
	}
	
	if ($itemid === 0) { 
		//Creating new item
		if ($itemtype =& newItemType($itemdata['type'],false,true)
			AND $itemtype->classname) {
			$item =& new $itemtype->classname($itemdata);
		} else {
			return false;
		}
	} else {
		$itemids = array();
		$itemdatas = array();
		if (is_array($itemid)) {
			//There are many itemids
			foreach ($itemid as $id) {
				if (is_numeric($id)
					AND $id > 0
					AND !isset($kbank_items[$id])) {
					//Itemid okay and it hasn't been cached
					$itemids[] = intval($id);
				}
			}
			if (count($itemids)) {
				$itemdata_cache = $vbulletin->db->query_read("
					SELECT 
						items.* 
						,user.username AS username
					FROM `" . TABLE_PREFIX . "kbank_items` AS items
					LEFT JOIN `" . TABLE_PREFIX . "user` AS user ON (user.userid = items.userid)
					WHERE items.itemid IN (" . implode(',',$itemids) . ")
				");
				
				DEVDEBUG('[kBank Item] newItem query the database');
				
				$itemids = array(); //Reset
				$itemtypeids = array();
				while ($itemdata = $vbulletin->db->fetch_array($itemdata_cache)) {
					$itemdatas[$itemdata['itemid']] = $itemdata;
					$itemids[] = $itemdata['itemid'];
					$itemtypeids[] = $itemdata['type'];
				}
				//Prepair itemtypes
				if (count($itemtypeids) > 1) {
					//Only prepair if there are more than 1 itemtype
					newItemType($itemtypeids,false,true);
				}
			}
		} else if (is_numeric($itemid)
			AND $itemid > 0) {
			//Only 1 itemid is passed in
			if (!isset($kbank_items[$itemid])) {
				//It has not been initialized, we can do it!
				$itemids[] = $itemid;
				
				if ($itemdata) {
					$itemdatas[$itemid] = $itemdata;
				} else {					
					$itemdatas[$itemid] = $vbulletin->db->query_first("
						SELECT 
							items.* 
							,user.username AS username
						FROM `" . TABLE_PREFIX . "kbank_items` AS items
						LEFT JOIN `" . TABLE_PREFIX . "user` AS user ON (user.userid = items.userid)
						WHERE itemid = $itemid
					");
					
					DEVDEBUG('[kBank Item] newItem query the database');
					
					if (!$itemdatas[$itemid]) {
						return false;
					}
				}
			} else {
				//Nothing to do!
				$item =& $kbank_items[$itemid];
			}
		}

		if (count($itemids) > 0
			AND count($itemids) == count($itemdatas)) {
			
			foreach ($itemids as $itemid) {
				$itemdata =& $itemdatas[$itemid];
				if ($itemtype =& newItemType($itemdata['type'],false,true)
					AND $itemtype->classname) {
					$kbank_items[$itemid] =& new $itemtype->classname($itemdata);
				} else {
					$kbank_items[$itemid] = false;
				}
			}
			
			if (count($itemids) == 1) {
				//Only return if passed in 1 itemid
				$item =& $kbank_items[$itemids[0]];
			}
		}
	}
	
	if ($item) {
		return $item;
	} else {
		return false;
	}
}
?>