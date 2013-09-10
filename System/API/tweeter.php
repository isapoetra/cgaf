<?php
namespace System\API;
class Tweeter extends PublicApi
{
    function __construct()
    {
        parent::__construct();
        $this->_apijs = array(
            'button' => \URLHelper::getCurrentProtocol() . '://platform.twitter.com/widgets.js');
    }

    function button($dataCount = 'vertical')
    {
        $this->init(__FUNCTION__);
        //<a href="https://twitter.com/share" class="twitter-share-button" data-count="vertical">Tweet</a><script type="text/javascript" src="//platform.twitter.com/widgets.js"></script>
        return '<a href="https://twitter.com/share" class="twitter-share-button" data-count="' . $dataCount . '">Tweet</a>';
    }

    function widget($tweet)
    {
        $id = \Utils::generateId('tweet');
        $this->getAppOwner()->addClientAsset('http://widgets.twimg.com/j/2/widget.js');
        $js = <<< EOF
new TWTR.Widget({
	    version: 2,
	    type: 'profile',
	    rpp: 4,
	    interval: 30000,
	    width: 'auto',
	    height: 300,
	    id : '$id',
	    theme: {
	      shell: {
	        background: '#333333',
	        color: '#ffffff'
	      },
	      tweets: {
	        background: '#000000',
	        color: '#ffffff',
	        links: '#4aed05'
	      }
	    },
	    features: {
	      scrollbar: false,
	      loop: false,
	      live: false,
	      behavior: 'all'
	    }
	  }).render().setUser('$tweet').start();
EOF;
        $this->getAppOwner()->addClientScript($js);
        return '<div id="' . $id . '"></div>';
    }
}
