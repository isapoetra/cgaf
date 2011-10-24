<?php
$appId = CGAF::getConfig('external.facebook.AppId');
$this->addHeader('xmlns',"http://www.w3.org/1999/xhtml");
$this->addHeader('xmlns:fb',"http://www.facebook.com/2008/fbml");
?>
<p>
	<script src="http://connect.facebook.net/en_US/all.js">
	<fb:login-button autologoutlink="true"></fb:login-button></p>
    <p><fb:like></fb:like></p>
    <div id="fb-root"></div>
    <script>
      window.fbAsyncInit = function() {
        FB.init({appId: '143929612345796', status: true, cookie: true,
                 xfbml: true});
      };
      (function() {
        var e = document.createElement('script');
        e.type = 'text/javascript';
        e.src = document.location.protocol +
          '//connect.facebook.net/en_US/all.js';
        e.async = true;
        document.getElementById('fb-root').appendChild(e);
      }());
    </script>