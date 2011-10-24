<?php
use System\Web\Utils\HTMLUtils, System\Auth\OpenId;
if ($response) {
	// Check the response status.
	if ($response->status == Auth_OpenID_CANCEL) {
		// This means the authentication was cancelled.
		$msg .= 'Verification cancelled.';
	} else if ($response->status == Auth_OpenID_FAILURE) {
		// Authentication failed; display the error message.
		$msg .= "OpenID authentication failed: " . $response->message;
	} else if ($response->status == Auth_OpenID_SUCCESS) {
		// This means the authentication succeeded; extract the
		// identity URL and Simple Registration data (if it was
		// returned).
		$openid = $response->getDisplayIdentifier();
		$esc_identity = escape($openid);

		$success = sprintf('You have successfully verified ' . '<a href="%s">%s</a> as your identity.', $esc_identity, $esc_identity);

		if ($response->endpoint->canonicalID) {
			$escaped_canonicalID = escape($response->endpoint->canonicalID);
			$success .= '  (XRI CanonicalID: ' . $escaped_canonicalID . ') ';
		}

		$sreg_resp = Auth_OpenID_SRegResponse::fromSuccessResponse($response);

		$sreg = $sreg_resp->contents();

		if (@$sreg['email']) {
			$success .= "  You also returned '" . escape($sreg['email']) . "' as your email.";
		}

		if (@$sreg['nickname']) {
			$success .= "  Your nickname is '" . escape($sreg['nickname']) . "'.";
		}
		if (@$sreg['fullname']) {
			$success .= "  Your fullname is '" . escape($sreg['fullname']) . "'.";
		}

		$pape_resp = Auth_OpenID_PAPE_Response::fromSuccessResponse($response);

		if ($pape_resp) {
			if ($pape_resp->auth_policies) {
				$success .= "<p>The following PAPE policies affected the authentication:</p><ul>";

				foreach ($pape_resp->auth_policies as $uri) {
					$escaped_uri = escape($uri);
					$success .= "<li><tt>$escaped_uri</tt></li>";
				}

				$success .= "</ul>";
			} else {
				$success .= "<p>No PAPE policies affected the authentication.</p>";
			}

			if ($pape_resp->auth_age) {
				$age = escape($pape_resp->auth_age);
				$success .= "<p>The authentication age returned by the " . "server is: <tt>" . $age . "</tt></p>";
			}

			if ($pape_resp->nist_auth_level) {
				$auth_level = escape($pape_resp->nist_auth_level);
				$success .= "<p>The NIST auth level returned by the " . "server is: <tt>" . $auth_level . "</tt></p>";
			}

		} else {
			$success .= "<p>No PAPE response was sent by the provider.</p>";
		}
	}
}
echo '<div class="openid-message">';
$err = Request::get('__msg');
if ($msg) {
	print "<div class=\"alert\">$msg</div>";
}
if ($error) {
	print "<div class=\"error\">$error</div>";

}
if ($err) {
	print "<div class=\"error\">$err</div>";
}
if ($success) {
	print "<div class=\"success\">$success</div>";
}
echo '</div>';
echo HTMLUtils::beginForm(BASE_URL . 'auth/openid/', false, false, null, array('id' => 'open-id-selector', 'class' => 'openid'));
echo '<ul class="openid-selector">';
$idx = 0;
foreach ($providers as $k => $provider) {
	if ($provider === '---') {
		echo '<li class="delim"></li>';
		$idx++;
	} else {
		$descr = isset($provider['descr']) ? $provider['descr'] : $k;
		$url = isset($provider['url']) ? $provider['url'] : '';
		echo '<li class="openid-' . $k . ' item-group-' . $idx . ' username" title="' . $descr . '">';
		$image = $this->getAppOwner()->getLiveAsset('openid/' . $provider['images']);
		echo '<img src="' . $image . '" alt="icon" />';
		echo '<span>' . $url . '</span>';
		echo '</li>';
	}
}
echo '</ul>';
$policies = OpenId::getPapePolicyURIS();
if ($policies) {
	echo '<div class="ui-helper-reset ui-helper-clearfix pape">';
	echo '<p>Optionally, request these PAPE policies:</p>';
	foreach ($policies as $i => $uri) {
		echo "<input type=\"checkbox\" name=\"policies[]\" value=\"$uri\" />";
		echo "$uri<br/>";
	}
	echo '</div>';
}

echo HTMLUtils::renderHiddenField('openid_provider', '');
echo HTMLUtils::renderHiddenField('action', 'verify');
echo '<span id="selected-provider" style="display:none;"></span>';
echo HTMLUtils::renderTextBox('Enter your <a class="openid_logo" href="http://openid.net">OpenID</a><span>&nbsp;</span>', 'openid_identifier', null);
echo '<span id="openid-url"></span>';
echo HTMLUtils::endForm(true, true);
$this->GetAppOwner()->addClientScript("$('#open-id-selector').openid();");
