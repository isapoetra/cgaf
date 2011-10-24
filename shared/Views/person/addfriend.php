<style>
.addfriend .foto {
	display: block;
	float: left;
	margin-right: 10px;
	width: 100px;
}

.addfriend .body {
	float: left;
	width: 313px;
}

.addfriend .button-message {
	float: left;
}

textarea.user_message {
	margin-top: 2px;
	width: 305px;
}
</style>
<div class="addfriend dialog" style="width: 450px; height: 200px">

	<h2 class="dlg_title">

		<?php echo sprintf(__('person.addfriend.ask'), $row->fullname) ?></h2>

  <?php
echo HTMLUtils::beginForm(BASE_URL . '/person/af', false, true);
echo HTMLUtils::renderHiddenField('id', $row->user_id);
																			  ?>
	<div class="ui-helper-clearfix">
		<div >
				<div class="foto">
					<img src="<?php echo BASE_URL . '/user/pic/?uid=' . $row->user_id . '&w=80&h=80' ?>"/>
				</div>
				<div class="body">
					<em><?php echo sprintf(__("person.addfriend.info"), $row->fullname); ?></em>
					<div id="addfriend-message" class="ui-helper-hidden">
					<?php
echo HTMLUtils::renderTextArea(__('message'), 'message', '', 'class="user_message"');
																		 ?>
					</div>
				</div>
		</div>
	</div>
	<div class="dlg_buttons ui-helper-clearfix">
		<div >
		<div class="button-message"><?php echo HTMLUtils::renderLink('', __('person.addfriend.message'), array('onclick' => '$(this).hide();$(\'#addfriend-message\').slideDown();return false;')) ?>
		</div><?php
echo HTMLUtils::renderButton('submit', __('person.addfriend.request.title'));
			  ?>
		</div>
	</div>
	<?php
//echo $this->render('person/single',true,false, array('row'=>$row,'renderaction'=>false,'mode'=>'addfriend'));
echo HTMLUtils::endForm(false, true);
		  ?>
</div>
