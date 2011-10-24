<?php
echo $this->render('shared/header', true, false);
echo ('hi');
?>
<table>
	<tr>
		<td>
		<?php echo $this->render('shared/applist', true, false); ?>
		</td>
	</tr>
</table>