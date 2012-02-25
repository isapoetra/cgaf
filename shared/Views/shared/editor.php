<?php
use System\Web\UI\Controls\FileEditor;
use System\Web\Utils\HTMLUtils;
$configs = isset ( $configs ) ? $configs : array ();
$editor = new FileEditor ( $file, $configs );
$params = isset($params) ? $params : array();
if (CGAF_DEBUG) {
	echo $file;
}
$editor->setId ( 'fcontent' );
?>
<div class="editor">

	<?php
	echo '<h2>' . $title . '</h2>';
	echo '<h3>' . $subtitle . '</h3>';
	if ($params) {
		?>
	<div>
		<h2>Valid Parameter</h2>
		<ul>
			<?php		
foreach ( $params as $k => $v ) {
			echo '<li>' . $k . ' : ' . $v . '</li>';
		}
		?>
		</ul>
	</div>
	<?php } ?>
	<div>
		<?php
		echo HTMLUtils::beginForm ( '', false );
		echo $editor->Render ( true );
		echo HTMLUtils::endForm ( true, true, true );
		?>
	</div>
</div>