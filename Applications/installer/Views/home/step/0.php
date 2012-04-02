<?php
echo <<< HEAD
<table class="table table-bordered">
<thead>
<tr>
	<th rowspan="2">Status</th>
	<th rowspan="2">Path</th>
	<th colspan="3">Current</th>
	<th colspan="3">Required</th>
</tr>
<tr>
	<th>User</th>
	<th>Group</th>
	<th>Permission</th>
	<th>User</th>
	<th>Group</th>
	<th>Permission</th>
</tr>
</thead>
HEAD;

foreach ($dirs as $d) {
	$p = new \FileInfo($d[0]);
	$owner =$p->owner;
	$c = $p->permEqual($d[1]);
	$w = $owner['user_posix']['name'] === $d[2] && $owner['group_posix']['name'] ===$d[3];
	echo '<tr>';
	echo '<td style="text-align:center;background-color:'.($c ? ($w ? 'green' :'yellow') : 'red').'"><i class="'.($c ? 'icon-ok' : 'icon-remove-circle').'"></i></td>';
	echo '<td>'.$d[0].'</td>' ;
	echo '<td>'.$owner['user_posix']['name'].'</td>';
	echo '<td>'.$owner['group_posix']['name'].'</td>';
	echo '<td>'.$p->perms['octal2'].'</td>';
	echo '<td style="background-color:'.($owner['user_posix']['name']===$d[2] ? 'green' :'yellow').'">'.$d[2].'</td>';
	echo '<td style="background-color:'.($owner['group_posix']['name']===$d[3] ? 'green' :'yellow').'">'.$d[3].'</td>';
	echo '<td style="background-color:'.($p->perms['octal2'] ===$d[1] ? 'green' :'red').'">'.$d[1].'</td>';
	echo '</tr>';
}
echo '</table>';
?>