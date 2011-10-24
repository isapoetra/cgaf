<?php
use \System\Web\Utils\HTMLUtils;
use \System\Auth\Auth;
$redirect = isset($redirect) ? $redirect : URLHelper::addParam(BASE_URL, array(
		'redirect' => Request::get("redirect"),
		'__t' => time()));
?>
<table width="100%" cellspacing="0" cellpadding="0" border="0"
	class="login-form">
	<tr>
		<td width="75%">&nbsp;<?php echo $this->getController()->renderContent('left')?></td>
		<td class="login-container">
<?php
$cssClass = isset($cssClass) ? $cssClass : 'login';
$renderNext = isset($renderNext) ? $renderNext : false;
$json = isset($json) ? $json : false;
echo HTMLUtils::beginForm(BASE_URL . 'auth/?' . ($json ? '_json=1' : ''), false, true, Request::get('msg'), array(
		'class' => $cssClass,
		'id' => 'login'));
echo '<input type="hidden" name="redirect" value="' . $redirect . '">';
echo HTMLUtils::renderTextBox(__('user.user_name'), 'username', null, 'class="required"', true) . '<br/>';
echo HTMLUtils::renderPassword(__('user.user_password'), 'password', null, 'class="required"', true);
echo HTMLUtils::renderCheckbox(__('auth.remember'), 'remember', false);
echo '<div class="formAction">';
echo HTMLUtils::renderButton('submit', __('auth.login.title', 'Sign In'), 'Login to System', array(), true, 'login-small.png');
echo '</div>';
echo HTMLUtils::renderLink(BASE_URL . 'user/forget/', __('user.forgetpassword'));
echo HTMLUtils::renderLink(BASE_URL . 'user/register/', __('user.register'));
echo HTMLUtils::endForm(false, true);
if ($providers) {
	$script = <<<EOT
	$('.auth-external').click(function(e) {
		e.preventDefault();
		var  screenX    = typeof window.screenX != 'undefined' ? window.screenX : window.screenLeft,
		screenY    = typeof window.screenY != 'undefined' ? window.screenY : window.screenTop,
		outerWidth = typeof window.outerWidth != 'undefined' ? window.outerWidth : document.body.clientWidth,
		outerHeight = typeof window.outerHeight != 'undefined' ? window.outerHeight : (document.body.clientHeight - 22),
		width    = 500,
		height   = 270,
		left     = parseInt(screenX + ((outerWidth - width) / 2), 10),
		top      = parseInt(screenY + ((outerHeight - height) / 2.5), 10),
		features = (
			'width=' + width +
			',height=' + height +
			',left=' + left +
			',top=' + top
		);
		var url =cgaf.url($(this).attr('href'),{popupmode:true}).toString();
		newwindow=window.open(url,'CGAF External Login',features);
		newwindow.onclose = function() {
			document.location.reload();
		}
		if (window.focus) {newwindow.focus()}
		return false;
			});
EOT;
	$appOwner->addClientScript($script);
	echo '<div class="auth-external-container">';
	echo '<div class="ui-widget-header">' . __('auth.alternative') . '</div>';
	foreach ($providers as $provider) {
		echo HTMLUtils::renderLink(\URLHelper::add(APP_URL, 'auth/external', 'id=' . $provider), __('auth.' . $provider . 'title', ucfirst($provider)),
				array(
						'title' => __('auth.' . $provider . 'descr', 'Signin using ' . ucfirst($provider)),
						'class' => 'auth-external auth-external-' . $provider), 'auth/' . $provider . '.png');
	}
	echo '</div>';
}
									?>
		</td>
	</tr>
</table>
