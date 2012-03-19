<?php
use System\Web\Utils\HTMLUtils;
use System\Session\Session;

$stepcontent = $this->Render('step/' . $cstep, true, false, array(
                                                                 'values' => $postvalues
                                                            ));
echo HTMLUtils::beginForm('');
echo HTMLUtils::renderHiddenField('step', $cstep);
echo HTMLUtils::renderHiddenField('nstep', $nstep);
?>
<div class="container-fluid">
	<div class="row-fluid">
		<div class="span3">
			<div class="well sidebar-nav">
				<ul class="nav nav-list">
					<li class="nav-header">Installation Step</li>
					<?php
					foreach ($steps as $k => $s) {
						echo '<li ' . ($cstep === $k ? 'class="active"' : '') . '>';
						$icon = '';
						if ($cstep === $k) {
							$icon = '<i class="icon-play"></i>';
						} else {
							$icon = '<i class="icon-ok"></i>';
						}
						echo HTMLUtils::renderLink(\URLHelper::add(BASE_URL, null, 'step=' . $k), $icon . $s ['title']);
						echo '</li>';
					}
					?>
				</ul>
			</div>
		</div>
		<div class="span9" id="step-<?php echo $cstep;?>-container">
			<?php
			foreach ($posterror as $v) {
				if (is_string($v)) {
					echo '<div class="label label-error">' . $v . '</div>';
				}
			}
			echo $stepcontent;
			?>
		</div>
	</div>
</div>
<div class="form-actions" style="text-align: right">
	<button name="next">Next</button>
</div>
<?php
echo HTMLUtils::endForm(false, true);
?>