<?php
namespace System\Controllers;
use System\Web\JS\CGAFJS;
use System\Exceptions\UnimplementedException;
use System\Exceptions\SystemException;
use System\MVC\Controller;
use Request;
use CGAF;
class Share extends Controller {
	private $_instances = array ();
	private $_providers = array (
			'config' => array (
					'url' => BASE_URL,
					'description' => '',
					'title' => '',
					'tags' => ''
			),
			'shareurl' => array (
					'url' => array (
							'Facebook' => array (
									'shareurl' => 'http://www.facebook.com/sharer.php?u={url}&t={description}',
									'descr' => 'Share with facebook'
							),
							'Twitter' => array (
									'shareurl' => 'http://twitter.com/home?status={description}:{url}'
							),
							'Delicious' => array (
									'shareurl' => 'http://del.icio.us/post?url={url}&title={title}&tags:{tags}&notes={description}'
							),
							'Digg' => array (
									'shareurl' => 'http://digg.com/submit?phase=2&url={url}&title{title}'
							),
							'Google' => array (
									'shareurl' => 'http://www.google.com/bookmarks/mark?op=add&bkmk={url}&title={title}&labels={tags}&annotation={description}'
							),
							'Yahoo' => array (
									'shareurl' => 'http://bookmarks.yahoo.com/toolbar/savebm?u={url}&t={title}&d={description}'
							),
							'Reddit' => array (
									'shareurl' => 'http://reddit.com/submit?url={url}'
							),
							'Linkedin' => array (
									'shareurl' => 'http://www.linkedin.com/shareArticle?mini=true&url={url}&title={title}&source=&summary={description}'
							),
							'Stumbleupon' => array (
									'shareurl' => 'http://www.stumbleupon.com/submit?url={url}&title={title}'
							)
					)
			) /*,'google' => array(
																												         'config' => array(),
																												         'plusOne' => array()),
																												 'facebook' => array(
																												         'like' => array())*/);
	function __construct($appOwner) {
		parent::__construct ( $appOwner, 'share' );
	}
	function isAllow($access = 'view') {
		switch ($access) {
			case 'view' :
			case 'index' :
			case 'api' :
				return true;
				break;
		}
		return parent::isAllow ( $access );
	}
	private function getInstance($name) {
		if (isset ( $this->_instances [$name] )) {
			return $this->_instances [$name];
		}
		$cname = '\\System\\API\\' . strtolower ( $name );
		$this->_instances [$name] = new $cname ( $this->getAppOwner () );
		return $this->_instances [$name];
	}
	private function share($id = null) {
		$id = $id ? $id : Request::get ( 'id' );
		if (! $id) {
			throw new SystemException ( 'invalid id' );
		}
		$configs ['url'] = Request::get ( 'url' );
		$configs ['description'] = Request::get ( 'description' );
		$configs ['title'] = Request::get ( 'title' );
		foreach ( $configs as $k => $v ) {
			if (! $v) {
				throw new SystemException ( "Invalid Configuration, empty value for %s", $k );
			}
		}
		if (! \Strings::BeginWith ( BASE_URL, $configs ['url'] )) {
			throw new SystemException ( 'sharing external url not allowed' );
		}
		$configs ['tags'] = Request::get ( 'tags', CGAF::getConfig ( 'cgaf.tags' ) );
		$s = $this->_providers [Request::get ( 'service' )] ['url'] [$id] ['shareurl'];
		$url = $v = \Strings::Replace ( $s, $configs, $s, true, null, '{', '}', true );
		// TODO Tracking
		\Response::Redirect ( $url );
	}
	function Index() {
		if (Request::get ( 'id' )) {
			return $this->share ();
		}
		return parent::Index ();
	}
	function initAction($action, &$params) {
		/*
		 * if (!\Request::isDataRequest()) {
		 * ppd($this->getAppOwner()->getLiveAsset('share.css')); }
		 */
		$action = strtolower ( $action );
		switch ($action) {
			case 'index' :
				$this->getAppOwner ()->addClientAsset ( CGAFJS::getPluginURL ( 'social-share' ) );
				$route = $this->getAppOwner ()->getRoute ();
				$params ['direct'] = $action === 'index' && $route ['_c'] === 'share';
				$share = $this->_providers;
				$content = '';
				if (! CGAF_DEBUG) {
					throw new UnimplementedException ();
				}
				$configs ['url'] = Request::get ( 'url', BASE_URL );
				$configs ['description'] = Request::get ( 'description', CGAF::getConfig ( 'cgaf.description' ) );
				$configs ['title'] = Request::get ( 'title', 'Cipta Graha Application Framework' );
				$configs ['tags'] = Request::get ( 'tags', CGAF::getConfig ( 'cgaf.tags' ) );
				foreach ( $configs as $k => $v ) {
					if (! $v) {
						throw new SystemException ( "Invalid Configuration, empty value for %s", $k );
					}
				}
				foreach ( $share as $k => $v ) {
					if ($k === 'config')
						continue;
					$instance = $this->getInstance ( $k );
					$instance->setConfigs ( $configs );
					foreach ( $v as $kk => $vv ) {
						if ($kk === 'config') {
							$instance->setConfigs ( $vv );
						} else {
							$content .= $instance->$kk ( $vv );
						}
					}
					$params ['shared'] = $content;
				}
		}
	}
}
