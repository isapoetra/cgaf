<table>
	<tr>
		<td colspan="2">
		<?php echo $this->render('person/search', true, false); ?></td>
	</tr>
	<tr>
		<td>
			<div>
				<ul>
<?php
if (!count($rows)) {
	echo '<li>' . __('person.friend.nofriend') . '</li>';
} else {
	foreach ($rows as $row) {
		$row = $this->getAppOwner()->getUserInfo($row->to_person);
		echo '<li>' . $this->render('userstatus', true, false, array('row' => $row)) . '</li>';
	}
}
					?>
				</ul>
			</div></td>
		<td>
		<?php $this->getAppOwner()->renderContent('user-friend-right') ?></td>
	</tr>
</table>
