<?php
/*======================================================================*\
|| #################################################################### ||
|| # vS-Hide Hack Resurrection (Expanded Edition) for vBulletin 3.5.x - 3.8.x by Anton Kanevsky
|| #################################################################### ||
|| # Copyright ©2006-2009 Anton Kanevsky (ankan925@gmail.com) aka @kan. All Rights Reserved.
|| # This file may not be redistributed.
|| #################################################################### ||
\*======================================================================*/

if (!class_exists('vB_XML_Builder'))
{
	class vB_XML_Builder
	{
		var $registry = null;
		var $charset = 'windows-1252';
		var $content_type = 'text/xml';
		var $open_tags = array();
		var $tabs = "";

		function vB_XML_Builder(&$registry, $content_type = null, $charset = null)
		{
			if (is_object($registry))
			{
				$vbulletin =& $registry;
			}
			else
			{
				trigger_error("vB_XML_Builder::Registry object is not an object", E_USER_ERROR);
			}

			if ($content_type)
			{
				$this->content_type = $content_type;
			}

			if ($charset == null)
			{
				$charset = $vbulletin->userinfo['lang_charset'];
			}

			$this->charset = (strtolower($charset) == 'iso-8859-1') ? 'windows-1252' : $charset;
		}

		/**
		* Sends the content type header with $this->content_type
		*/
		function send_content_type_header()
		{
			@header('Content-Type: ' . $this->content_type . ($this->charset == '' ? '' : '; charset=' . $this->charset));
		}

		/**
		* Returns the <?xml tag complete with $this->charset character set defined
		*
		* @return	string	<?xml tag
		*/
		function fetch_xml_tag()
		{
			return '<?xml version="1.0" encoding="' . $this->charset . '"?>' . "\n";
		}

		function add_group($tag, $attr = array())
		{
			$this->open_tags[] = $tag;
			$this->doc .= $this->tabs . $this->build_tag($tag, $attr) . "\n";
			$this->tabs .= "\t";
		}

		function close_group()
		{
			$tag = array_pop($this->open_tags);
			$this->tabs = substr($this->tabs, 0, -1);
			$this->doc .= $this->tabs . "</$tag>\n";
		}

		function add_tag($tag, $content = '', $attr = array(), $cdata = false)
		{
			$this->doc .= $this->tabs . $this->build_tag($tag, $attr, ($content === ''));
			if ($content !== '')
			{
				if ($cdata OR preg_match('/[\<\>\&\'\"\[\]]/', $content))
				{
					$this->doc .= '<![CDATA[' . $this->escape_cdata($content) . ']]>';
				}
				else
				{
					$this->doc .= $content;
				}
				$this->doc .= "</$tag>\n";
			}
		}

		function build_tag($tag, $attr, $closing = false)
		{
			$tmp = "<$tag";
			if (!empty($attr))
			{
				foreach ($attr AS $attr_name => $attr_key)
				{
					if (strpos($attr_key, '"') !== false)
					{
						$attr_key = htmlspecialchars_uni($attr_key);
					}
					$tmp .= " $attr_name=\"$attr_key\"";
				}
			}
			$tmp .= ($closing ? " />\n" : '>');
			return $tmp;
		}

		function escape_cdata($xml)
		{
			// strip invalid characters in XML 1.0:  00-08, 11-12 and 14-31
			// I did not find any character sets which use these characters.
			$xml = preg_replace('#[\\x00-\\x08\\x0B\\x0C\\x0E-\\x1F]#', '', $xml);

			return str_replace(array('<![CDATA[', ']]>'), array('«![CDATA[', ']]»'), $xml);
		}

		function output()
		{
			if (!empty($this->open_tags))
			{
				trigger_error("There are still open tags within the document", E_USER_ERROR);
				return false;
			}

			return $this->doc;
		}

		function print_xml()
		{
			if (defined('NOSHUTDOWNFUNC'))
			{
				$vbulletin->db->close();
			}

			$this->send_content_type_header();
			echo $this->fetch_xml_tag() . $this->output();
			exit;
		}
	}

	// #############################################################################

	class vB_AJAX_XML_Builder extends vB_XML_Builder
	{
		function escape_cdata($xml)
		{
			$xml = preg_replace('#[\\x00-\\x08\\x0B\\x0C\\x0E-\\x1F]#', '', $xml);

			return str_replace(array('<![CDATA[', ']]>'), array('<=!=[=C=D=A=T=A=[', ']=]=>'), $xml);
		}
	}
}

if ($vbulletin->options['enable_hrply_tag'] AND $_REQUEST['do'] == 'hhr_get_posts')
{
	$vbulletin->input->clean_gpc('r', 'postids', TYPE_STR);
	
	$postids = array();
	foreach (explode(',', $vbulletin->GPC['postids']) as $postid)
	{
		if ($postid && is_numeric($postid) && !in_array($postid, $postids))
		{
			$postids[] = $postid;
		}
	}
	
	$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
	$xml->add_group('posts');
	
	if (sizeof($postids))
	{
		$postids = implode(',', $postids);

		$getposts = $db->query_read("
			SELECT post.postid, post.threadid, post.userid, post.pagetext, post.allowsmilie, post.thankscache, post.attach, thread.forumid, user.thankedcount
			FROM " . TABLE_PREFIX . "post as post
			LEFT JOIN " . TABLE_PREFIX . "thread as thread ON (thread.threadid = post.threadid)
			LEFT JOIN " . TABLE_PREFIX . "user as user ON (user.userid = post.userid)
			WHERE postid IN ($postids)
		");
		
		if ($db->num_rows($getposts))
		{
			// initialize bbcode parser
			require_once(DIR . '/includes/class_bbcode.php');
			$bbcode_parser = new vB_BbCodeParser($vbulletin, fetch_tag_list());
			
			// construct posts
			while ($postinfo = $db->fetch_array($getposts))
			{
				// set thankscache source
				$hhr->set_thankscache($postinfo['thankscache']);
				
				// do attachments
				$bbcode_parser->attachments = array();
				
				if ($postinfo['attach'])
				{
					$attachments = $db->query_read_slave("
						SELECT dateline, thumbnail_dateline, filename, filesize, visible, attachmentid, counter,
							postid, IF(thumbnail_filesize > 0, 1, 0) AS hasthumbnail, thumbnail_filesize,
							attachmenttype.thumbnail AS build_thumbnail, attachmenttype.newwindow
						FROM " . TABLE_PREFIX . "attachment
						LEFT JOIN " . TABLE_PREFIX . "attachmenttype AS attachmenttype USING (extension)
						WHERE postid = " . $postinfo['postid'] . "
						ORDER BY attachmentid
					");
					while ($attachment = $db->fetch_array($attachments))
					{
						if (!$attachment['build_thumbnail'])
						{
							$attachment['hasthumbnail'] = false;
						}
						$bbcode_parser->attachments["$attachment[attachmentid]"] = $attachment;
					}
				}
			
				// construct message
				$postinfo['message'] = $hhr->parse_bbcode($bbcode_parser->parse(
					$postinfo['pagetext'],
					$postinfo['forumid'],
					$postinfo['allowsmilie']
				), $postinfo['forumid'], $postinfo['threadid'], $postinfo['postid'], $postinfo['userid']);
				
				// construct xml
				$xml->add_group('post');
				$xml->add_tag('postid', $postinfo['postid']);
				$xml->add_tag('message', $postinfo['message']);
				$xml->close_group();
			}
		}
	}
	
	$xml->close_group();
	$xml->print_xml();
}

if ($show['hidetag_thankyou_system'])
{
	if ($postinfo)
	{
		if ($_REQUEST['do'] == 'thanks')
		{
			$result = $hhr->insert_thanks($postinfo, $threadinfo, $foruminfo, false);
		}

		if ($_REQUEST['do'] == 'removethanks')
		{
			$vbulletin->input->clean_gpc('r', 'userid', TYPE_INT);
			$result = $hhr->delete_thanks($postinfo, $threadinfo, $foruminfo, $vbulletin->GPC['userid'], false);
		}
	}
	else
	{
		$result = false;
	}

	if ($_REQUEST['do'] == 'thanks' || $_REQUEST['do'] == 'removethanks')
	{
		$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
		$xml->add_group('tdata');
		
		if ($result)
		{
			if ($result == 9999)
			{
				$xml->add_tag('status', 'unregistered');
			}
			else
			{
				$userinfo = $db->query_first("
					SELECT userid, thankedcount
					FROM " . TABLE_PREFIX . "user
					WHERE userid = " . $postinfo['userid'] . "
				");
				
				if (!$userinfo)
				{
					$userinfo = array(
						'userid' => 0,
						'thankedcount' => 0,
					);
				}
				
				$postinfo = $postinfo + $userinfo;
			
				// construct data...
				$thanks_bit = $hhr->build_thanks($postinfo);				
				$postid =& $postinfo['postid'];
				$posterid =& $postinfo['userid'];
				
				// format variables...
				$post_thanks_count = vb_number_format($post_thanks_count);
				$user_thanks_count = vb_number_format($user_thanks_count);
				
				// construct message...
				require_once(DIR . '/includes/class_bbcode.php');

				$bbcode_parser = new vB_BbCodeParser($vbulletin, fetch_tag_list());
				
				// do attachments...
				$bbcode_parser->attachments = array();
				
				if ($postinfo['attach'])
				{
					$attachments = $db->query_read_slave("
						SELECT dateline, thumbnail_dateline, filename, filesize, visible, attachmentid, counter,
							postid, IF(thumbnail_filesize > 0, 1, 0) AS hasthumbnail, thumbnail_filesize,
							attachmenttype.thumbnail AS build_thumbnail, attachmenttype.newwindow
						FROM " . TABLE_PREFIX . "attachment
						LEFT JOIN " . TABLE_PREFIX . "attachmenttype AS attachmenttype USING (extension)
						WHERE postid = " . $postinfo['postid'] . "
						ORDER BY attachmentid
					");
					while ($attachment = $db->fetch_array($attachments))
					{
						if (!$attachment['build_thumbnail'])
						{
							$attachment['hasthumbnail'] = false;
						}
						$bbcode_parser->attachments["$attachment[attachmentid]"] = $attachment;
					}
				}
				
				// process message...
				$postinfo['message'] = $hhr->parse_bbcode($bbcode_parser->parse(
					$postinfo['pagetext'],
					$foruminfo['forumid'],
					$postinfo['allowsmilie']
				), $foruminfo['forumid'], $threadinfo['threadid'], $postinfo['postid'], $postinfo['userid']);
				
				// define template conditionals...	
				if ($hhr->is_thankable($postinfo, $threadinfo, $foruminfo))
				{
					$show['post_thanks_postbtn'] = true;		
				}
				else
				{
					$show['post_thanks_postbtn'] = false;
				}

				// construct templates...
				eval('$thanks_wrapper = "' . trim(fetch_template('thanks_wrapper')) . '";');
				eval('$thanks_postbit = "' . trim(fetch_template('thanks_postbit')) . '";');				
				eval('$thanks_postbtn = "' . trim(fetch_template('thanks_postbtn')) . '";');
				
				// construct xml...
				$xml->add_tag('status', 'ok');
				$xml->add_tag('wrapper', $thanks_wrapper);
				$xml->add_tag('message', $postinfo['message']);
				$xml->add_tag('postbit', $thanks_postbit);
				$xml->add_tag('postbtn', $thanks_postbtn);
			}
		}
		else
		{
			$xml->add_tag('status', 'oops');
		}
		
		$xml->close_group();
		$xml->print_xml();
	}
}

/*======================================================================*\
|| #################################################################### ||
|| # vS-Hide Hack Resurrection (Expanded Edition) for vBulletin 3.5.x - 3.8.x by Anton Kanevsky
|| #################################################################### ||
\*======================================================================*/
?>