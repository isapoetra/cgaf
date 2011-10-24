<?php
echo '<div class="vote_result">';
if ($msg) {
	echo '<span class="warning">' . $msg . '</span>';
}
echo '<h3>' . __('vote.thankyou', 'Thank you for voting') . '</h3>';
echo '<a href="'.\URLHelper::add(APP_URL,'vote/detail','id='.$row->vote_id).'"><span class="title">' . $row->vote_title . '</span></a>';
echo '<table >';
//echo '<tr><th colspan="3">' . __('vote.result.descr') . '</th>';
$total = 0;
foreach ($items as $item) {
	$total += $item->vote_result;
}
foreach ($items as $item) {
	$w = (($item->vote_result / $total) * 100);
	echo '<tr><td>' . __($item->vote_title) . '</td>';
	echo '<td width="100%"><div class="vote-result-result" style="width:' . $w . '%">&nbsp;</div></td>';
	echo '<td>' . $w . '%</td>';
	echo '</tr>';
}
echo '</table>';
echo $this->getController()->renderContent('bottom');
echo '</div>';
?>