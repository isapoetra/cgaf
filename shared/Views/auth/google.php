<?php
use \Request;
$this->getAppOwner()->addClientAsset('popuplib.js');
$this->getAppOwner()->addClientAsset('auth-google.css');
$script = <<< EOT
function toggle(id, type) {
  if (type === 'list') {
    $('pre.' + id).hide();
    $('div.' + id).show();
  } else {
    $('div.' + id).hide();
    $('pre.' + id).show();
  }
}
var upgradeToken = function() {
    window.location = '$redirect';
  };
  var extensions = $openid_ext;
  var googleOpener = popupManager.createPopupOpener({
    'realm' : '$realm',
    'opEndpoint' : 'https://www.google.com/accounts/o8/ud',
    'returnToUrl' : '$return?popup=true',
    'onCloseHandler' : upgradeToken,
    'shouldEncodeUrls' : true,
    'extensions' : extensions
  });
  $(document).ready(function () {
    jQuery('#LoginWithGoogleLink').click(function() {
      googleOpener.popup(450, 500);
      return false;
    });
  });
EOT;
$this->getAppOwner()->addClientScript($script);
?>
<h3>
	<span class="google"><span>G</span><span>o</span><span>o</span><span>g</span><span>l</span><span>e</span>
	</span> Hybrid Protocol (<a href="http://openid.net">OpenID</a>+<a
		href="http://oauth.net">OAuth</a>) Demo [ <small><a
		href="http://code.google.com/apis/accounts/docs/OpenID.html">documentation</a>
	</small> ]
</h3>

<div style="float: left;">
	<img src="/assets/images/auth/hybrid_logo.png" />
</div>
<div>
	<form method="POST" action="<?php echo BASE_URL . 'auth/google' ?>">
		<fieldset>
			<legend>
				<small><b>Enter an OpenID:</b> </small>
			</legend>
			<input type="hidden" name="openid_mode" value="checkid_setup"> <input
				type="text" name="openid_identifier" id="openid_identifier"
				size="40" value="google.com/accounts/o8/id" /> <input type="submit"
				value="login" /> <br> Sign in with a <a
				href="<?php echo BASE_URL . 'auth/google/?openid_mode=checkid_setup&openid_identifier=google.com/accounts/o8/id' ?>"
				id="LoginWithGoogleLink"><img height="16" width="16"
				align="absmiddle" style="margin-right: 3px;"
				src="/assets/images/auth/gfavicon.gif" border="0" /><span
				class="google"><span>G</span><span>o</span><span>o</span><span>g</span><span>l</span><span>e</span>
					Account</span> </a> (popup)
		</fieldset>
	</form>
</div>
<?php if (Request::get('openid_mode') === 'id_res') { ?>
<p>
	Welcome:
	<?php echo "{$_REQUEST['openid_ext1_value_first']} {$_REQUEST['openid_ext1_value_last']} - {$_REQUEST['openid_ext1_value_email']}" ?>
	<br> country:
	<?php echo Request::get('openid_ext1_value_country') ?>
	<br> language:
	<?php echo $_REQUEST['openid_ext1_value_lang'] ?>
	<br>
</p>
<?php } ?>

<div style="margin-left: 140px;">
<?php if ($request_token && $access_token) : ?>
	Access token:
	<?php echo $access_token->key; ?>
	<br>




<?php else : ?>
  <h4 style="margin-top:5.5em;">You are not authenticated</h4>
<?php endif; ?>

<?php if (isset($data['docs'])) : ?>
  <h4>Your Google Docs:</h4>
  [ <a href="javascript:toggle('docs_data', 'list');">list</a> | <a href="javascript:toggle('docs_data', 'xml');">xml</a> ]
  <div class="docs_data"><?php echo $data['docs']['html']; ?></div>
  <pre class="data_area docs_data" style="display:none;"><?php echo xml_pp($data['docs']['xml'], true); ?></pre>
<?php endif; ?>

<?php if (isset($data['spreadsheets'])) : ?>
  <h4>Your Google Spreadsheets:</h4>
  [ <a href="javascript:toggle('spreadsheets_data', 'list');">list</a> | <a href="javascript:toggle('spreadsheets_data', 'xml');">xml</a> ]
  <div class="spreadsheets_data"><?php echo $data['spreadsheets']['html']; ?></div>
  <pre class="data_area spreadsheets_data" style="display:none;"><?php echo xml_pp($data['spreadsheets']['xml'], true); ?></pre>
<?php endif; ?>

<?php if (isset($data['poco'])) : ?>
  <h4>Your OpenSocial Portable Contacts Data:</h4>
  <pre class="data_area"><?php echo JSON::json_pp($data['poco']); ?></pre>
<?php endif; ?>
</div>
<?php if ($debugmsg) {
	echo "<ul class=\"errors\">";
	foreach ($debugmsg as $message) {
		echo "<li >$message</li>";
	}
	echo "</ul>";
}
	  ?>
