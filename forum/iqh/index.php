<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="vn">
<head>

	<title>Đăng Nhập - Forum 4fvn - vBulletin Admin Control</title>

	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

	<link rel="stylesheet" type="text/css" href="../cpstyles/global.css?v=405" />

	<link rel="stylesheet" type="text/css" href="../cpstyles/vBulletin_3_Silver/controlpanel.css?v=405" />

	<style type="text/css">

		.page { background-color:white; color:black; }

		.time { color:silver; }

		/* Start generic feature management styles */



		.feature_management_header {

			font-size:16px;

		}



		/* End generic feature management styles */





		/* Start Styles for Category Manager */



		#category_title_controls {

			padding-left: 10px;

			font-weight:bold;

			font-size:14px;

		}



		.picker_overlay {

			/*

				background-color:black;

				color:white;

			*/

			background-color:white;

			color:black;

			font-size:14px;

			padding:3px;

			border:1px solid black;

		}



		.selected_marker {

			margin-right:4px;

			margin-top:4px;

			float:left;

		}



		.section_name {

			font-size:14px;

			font-weight:bold;

			padding:0.2em 1em;

			margin: 0.5em 0.2em;

			/*

			color:#a2de97;

			background-color:black;

			*/

			background-color:white;

		}



		.tcat .picker_overlay a, .picker_overlay a, a.section_switch_link {

			/*

			color:#a2de97;

			*/

			color:blue;

		}



		.tcat .picker_overlay a:hover, .picker_overlay a:hover, a.section_switch_link:hover {

			color:red;

		}

		/* End Styles for Category Manager */

	</style>

	<script type="text/javascript">

	<!--

	var SESSIONHASH = "";

	var ADMINHASH = "ef4906c04e255e9b349ede29cb4b1a86";

	var SECURITYTOKEN = "guest";

	var IMGDIR_MISC = "../cpstyles/vBulletin_3_Silver";

	var CLEARGIFURL = "./clear.gif";

	function set_cp_title()

	{

		if (typeof(parent.document) != 'undefined' && typeof(parent.document) != 'unknown' && typeof(parent.document.title) == 'string')

		{

			parent.document.title = (document.title != '' ? document.title : 'vBulletin');

		}

	}

	//-->

	</script>

	<script type="text/javascript" src="../clientscript/yui/yuiloader-dom-event/yuiloader-dom-event.js"></script>

	<script type="text/javascript" src="../clientscript/yui/connection/connection-min.js"></script>

	<script type="text/javascript" src="../clientscript/vbulletin_global.js"></script>

	<script type="text/javascript" src="../clientscript/vbulletin-core.js"></script>

	<script type="text/javascript" src="../clientscript/vbulletin_ajax_suggest.js"></script>

</head>
<body style="margin:0px" onload="set_cp_title(); document.forms.loginform.vb_login_username.focus()">
<!-- END CONTROL PANEL HEADER -->


	<script type="text/javascript" src="../clientscript/vbulletin_md5.js?v=405"></script>

	<script type="text/javascript">

	<!--

	function js_show_options(objectid, clickedelm)

	{

		fetch_object(objectid).style.display = "";

		clickedelm.disabled = true;

	}

	function js_fetch_url_append(origbit,addbit)

	{

		if (origbit.search(/\?/) != -1)

		{

			return origbit + '&' + addbit;

		}

		else

		{

			return origbit + '?' + addbit;

		}

	}

	function js_do_options(formobj)

	{

		if (typeof(formobj.nojs) != "undefined" && formobj.nojs.checked == true)

		{

			formobj.url.value = js_fetch_url_append(formobj.url.value, 'nojs=1');

		}

		return true;

	}

	//-->

	</script>

	<form action="login.php?goto=" method="post" name="loginform">

	<input type="hidden" name="url" value="/forum/admincp/"/>

	<input type="hidden" name="s" value="bcde49935744cb63de74242a56f47340" />

	<input type="hidden" name="securitytoken" value="guest" />

	<input type="hidden" name="logintype" value="cplogin" />

	<input type="hidden" name="do" value="login" />

	<input type="hidden" name="vb_login_md5password" value="" />

	<input type="hidden" name="vb_login_md5password_utf" value="" />

	
	<p>&nbsp;</p>
	<table class="tborder" align="center" border="0" cellpadding="0" cellspacing="0" width="450">
	  <tr>
	    <td><!-- header -->
            <div class="tcat" style="padding:4px; text-align:center"><b>Đăng Nhập</b></div>
	      <!-- /header -->
            <!-- logo and version -->
            <table cellpadding="4" cellspacing="0" border="0" width="100%" class="navbody">
              <tr valign="bottom">
                <td><img src="../cpstyles/vBulletin_3_Silver/cp_logo.gif" alt="" title="vBulletin Phiên bản 4.0.5, Bản quyền &copy; 2000-2010, Jelsoft Enterprises Ltd." border="0" /></td>
                <td><b><a href="../forum.php">Forum 4fvn</a></b><br />
                  vBulletin 4.0.5 Admin Control<br />
                  &nbsp; </td>
              </tr>
            </table>
	      <!-- /logo and version -->
            <table cellpadding="4" cellspacing="0" border="0" width="100%" class="logincontrols">
              <col width="50%" style="text-align:right; white-space:nowrap" />
              <col />
              <col width="50%" />
              <!-- login fields -->
              <tbody>
                <tr>
                  <td>Ký danh</td>
                  <td><input type="text" style="padding-left:5px; font-weight:bold; width:250px" name="vb_login_username" value="" accesskey="u" tabindex="1" id="vb_login_username" /></td>
                  <td>&nbsp;</td>
                </tr>
                <tr>
                  <td>Mật khẩu</td>
                  <td><input type="password" style="padding-left:5px; font-weight:bold; width:250px" name="vb_login_password" accesskey="p" tabindex="2" id="vb_login_password" /></td>
                  <td>&nbsp;</td>
                </tr>
                <tr style="display: none" id="cap_lock_alert">
                  <td>&nbsp;</td>
                  <td class="tborder"><strong>Caps Lock is on!</strong><br />
                      <br />
                    Having Caps Lock on may cause you to enter your password incorrectly. You should press Caps Lock to turn it off before entering your password.</td>
                  <td>&nbsp;</td>
                </tr>
              </tbody>
              <!-- /login fields -->
              <!-- admin options -->
              <tbody id="loginoptions" style="display:none">
                <tr>
                  <td>Style</td>
                  <td><select name="cssprefs" class="login" style="padding-left:5px; font-weight:normal; width:250px" tabindex="5">
                      <option value="vBulletin_2_Default">vBulletin 2 Default</option>
                      <option value="vBulletin_3_Default">vBulletin 3 Default</option>
                      <option value="vBulletin_3_Frontend">vBulletin 3 Frontend</option>
                      <option value="vBulletin_3_Manual">vBulletin 3 Manual</option>
                      <option value="" selected="selected">vBulletin 3 Silver</option>
                  </select></td>
                  <td>&nbsp;</td>
                </tr>
                <tr>
                  <td>Options</td>
                  <td><label>
                    <input type="checkbox" name="nojs" value="1" tabindex="6" />
                    Save open navigation groups automatically</label>
                  </td>
                  <td class="login">&nbsp;</td>
                </tr>
              </tbody>
              <!-- END admin options -->
              <!-- submit row -->
              <tbody>
                <tr>
                  <td colspan="3" align="center"><input type="submit" class="button" value="  Đăng Nhập  " accesskey="s" tabindex="3" />
                      <input type="button" class="button" value=" Options " accesskey="o" onclick="js_show_options('loginoptions', this)" tabindex="4" />
                  </td>
                </tr>
              </tbody>
              <!-- /submit row -->
          </table></td>
      </tr>
	  </table>
	<p>&nbsp;</p>

</form>

	<script type="text/javascript">

	<!--

	

	

<!-- START CONTROL PANEL FOOTER -->

</body>
</html>