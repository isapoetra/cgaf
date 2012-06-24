<?php
use System\Web\Utils\HTMLUtils;

/**
 * @var \System\Applications\WebApplication $owner
 */
$owner = $this->getAppOwner();
$content = isset ($content) ? $content : $this->getContent();
if ($content) {
  echo $content;
  return;
}
$rows = \AppManager::AllowedApp();
$js
  = <<<EOT
//$('#app-info-container .content').shortscroll();
(function() {
	var ac = $('#app-info-container');
	var app;
	var a = $('#left-nav a');
	if (a.length ===0) {
		ac.find('ul .nav-header').hide();
	}
a.click(function(e){
	e.preventDefault();
	var c=ac.find('.content');
	$(this).parent().parent().children().removeClass('active');
	app = $(this).parent().addClass('active').attr('data-app');

	c.addClass('loading').load(cgaf.getConfig('baseurl') + '/about/app/?appid='+ app,function(){
		$(this).removeClass('loading');
	});
	ac.find('.action button').show();
});
ac.find('.action button').hide().click(function(e){
	if (app) {
		var a = $(this).attr('data-action');
		e.preventDefault();
		switch(a.toLowerCase()) {
		  case 'changelog':
		  	document.location = cgaf.getConfig('baseurl') + 'app/changelog/?appid=' + app;
		  	break;
			case 'open':
				document.location = cgaf.getConfig('baseurl') + '/?__appId=' + app;
				break;
		}
		console.log(app,a);
	}
});
$('#left-nav a:first').trigger('click');
})();
EOT;
$owner->AddClientScript($js);
?>
<div class="container-fluid">
  <div class="row-fluid">
    <div class="span2">
      <div class="well sidebar-nav">
        <ul class="nav nav-list" id="left-nav">
          <li class="nav-header">Applications</li>
          <?php
          $hasApp = count($rows) > 0;
          foreach ($rows as $r) {
            if ($r->app_id === \CGAF::APP_ID) {
              continue;
            }
            $link = BASE_URL . '?__appId=' . $r->app_id;
            $ricon = ($r->app_icon ? $r->app_icon : 'app' . '.png');
            $icon = ASSET_PATH . 'applications/' . $r->app_id . '/assets/images/icons/' . $ricon;
            if (is_file($icon)) {
              $icon = \Utils::PathToLive($icon);
            } else {
              $icon = BASE_URL . 'asset/'. $ricon.'?appId=' . $r->app_id;
            }
            echo '<li  data-app="' . $r->app_id . '">';
            echo HTMLUtils::renderLink(
              $link, '<i class="icon-book"></i>' . $r->app_name, array(
                'title' => $r->app_descr
              )
            );
            echo '</li>';
          }
          if (!$hasApp) {
            echo '<li class="label label-important">' . __('app.empty') . '</li>';
          }
          ?>
        </ul>
      </div>
    </div>
    <div id="app-info-container">
      <div class="content"></div>
      <ul class="nav nav-pills well action">
        <li>
          <button class="btn btn-small btn-info" data-action="changelog" style="display:none"><i
            class="icon-th-list"></i><?php __('changelog', 'Changelog');?></button>
          <button class="btn btn-small btn-primary" data-action="open" style="display:none"><i
            class="icon-ok"></i><?php __('open', 'Open');?></button>
        </li>
      </ul>
    </div>
  </div>
</div>