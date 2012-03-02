<?php
use System\API\PublicApi;
if ($rows) {
	echo '<div class="contact-list">';
	foreach ( $rows as $v ) {
		$api = PublicApi::getInstance ( $v->api );
		echo '<div class="' . $v->api . '-' . $v->type . '">' . $api->{ $v->type} ( $v->configs ) . '</div>';
	}
	echo '</div>';
}
