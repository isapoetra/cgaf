<?php
defined("CGAF") or die();
use \System\Web\Utils\HTMLUtils;
$pd = array();
if ($_POST) {
	$pd = isset($_POST['dir']) ? $_POST['dir'] : array();
}

$paths = \CGAF::getConfigs('cgaf.app.paths', array(CGAF_APP_PATH));
echo HTMLUtils::beginForm('.');
echo HTMLUtils::renderHiddenField('__mode','direct');
?>
<div>
	<fieldset>
		<legend>Put your application into the following folder</legend>
		<?php
		echo '<ul  class="nav nav-list">';
		$idx = 0;
		foreach ($paths as $p) {
			echo'<li><h3>' . $p . '</h3>';
			$dirs = \Utils::getDirList($p);
			echo '<ul class="nav nav-list">';
			foreach ($dirs as $dir) {
				$config = new \System\Configurations\Configuration(null, false);
				$fconfig = $p . DS . $dir . DS . 'config.php';
				echo '<li>';
				if (is_file($p . DS . $dir . DS . 'config.php')) {
					$config->loadFile($p . DS . $dir . DS . 'config.php');
					$name = $config->getConfig('app.shortname');
					$id = $config->getConfig('app.id');
					if (\AppManager::isAppInstalled($id, false) || $id === \CGAF::APP_ID) continue;
					if (!$name) $name = $dir;
					echo '<label  class="checkbox"><input type="checkbox" name="dir[]" value="' . $p . $dir . '" '.(in_array($p . $dir,$pd) ? ' checked="checked"' : '').'/>' . $name ;
					if (!is_readable($p . $dir.DS.'index.php')) {
						echo HTMLUtils::renderError($p . $dir.DS.'index.php not readable by webserver');
					}
					echo '</label>';
				} else {
					echo '<label  class="label label-important">folder ' . $dir . ' has no configuration file</label>';
				}
				echo '</li>';
				$idx++;
			}
			echo'</ul>';
			echo '</li>';
		}
		echo '</ul>';
		?>
		<hr class="divider"/>
		<div class="control-group">
			<label class="control-label" for="input01">Install from other location</label>

			<div class="controls warning">
				<input type="text" class="input-xlarge" id="other" name="other" value="<?echo isset($_POST['other']) ? $_POST['other'] : '';?>">

				<p class="help-block">Application path should writable by webserver</p>
			</div>
		</div>

		<?php
		echo \System\Web\Utils\HTMLUtils::endForm(true, true);
		?>
	</fieldset>

</div>
