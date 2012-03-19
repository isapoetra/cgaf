<?php
/**
 * update.php
 * User: e1
 * Date: 3/14/12
 * Time: 8:45 PM
 */
if (is_string($result)) {
	echo '<h4>Unexpected Server Response on Step '.$step.'</h4>';
	echo '<span class="label label-warning"><pre>'.$result.'</pre></span> ';
	return;
}
echo $this->renderView('update/'.$step);

