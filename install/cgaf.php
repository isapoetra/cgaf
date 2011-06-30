#!/usr/bin/env php
<?php
define("SITE_PATH", realpath(dirname(__FILE__).'/../'));
define("CGAF_CONFIG", true);
//uncomment this line to use default application (Web)
include "../System/cgaf.php";
define('CGAF_DEBUG',true);
echo 'Initializing CGAF Instance...'."\n";
CGAF :: Initialize();
if (!System::isConsole()) {
	die("Please run from console\n");
}

function rw($text,$nl=true) {
	if ($nl) {
		return Response::writeln($text);
	}else{
		return Response::write($text);
	}
}
class TCGAFApplication extends ConsoleApplication {
	private $_cgafConfig;
	private $_lastCheck;
	function __construct() {
		parent::__construct(CGAF_PATH,'__cgaf');
		$this->_cgafConfig = CGAF::geConfiguration();
	}
	function getConfig($val,$def=null) {
		return $this->_cgafConfig->getConfig($val,$def);
	}
	function setConfig($configName,$value=null) {
		return $this->_cgafConfig->setConfig($configName,$value);
	}
	private function about() {
		Response::writeLn(str_repeat('-',80));
		Response::writeLn("CGAF v.".CGAF_VERSION);

		Response::writeLn(str_repeat('-',80));
	}
	function getString($title=null) {
		if ($title) {
			rw($title,false);
		}
		$fp = fopen ("php://stdin","r");
		$in = fgets($fp, 4094);
		fclose ($fp);
		return rtrim($in);
	}
	function getChar($title=null) {
		if ($title) {
			rw($title,false);
		}
		$input = fgetc(STDIN);
		return $input;
	}
	private function renderMenu($menus = null,$back=true,$clear=true) {
		if ($clear) {
			Response::clearBuffer();
			$this->about();
		}
		$maxrow=10;
		if ($back) {
			$menus['x'] = isset($menus['x'])  ? $menus['x']  : 'Back';
		}
		foreach($menus as $k=>$t) {
			$title = $t;
			if (is_array($t)) {
				$title = isset($t['title'])  ? $t['title'] : $k;
			}
			rw('['.Response::getInstance()->writeColor($k,'red',null,null,true,true).']  : '.$title);
		}
		$c = $this->getString('Enter Selection :');
		if (! array_key_exists($c,$menus)) {
			rw('Valid Selection is ['.implode(array_keys($menus),',').']',false);
			$this->getString();
			return $this->renderMenu($menus);
		}
		$x = $menus[$c];
		if (is_array($x)) {
			return isset($x['value']) ? $x['value'] : $c;
		}
		return $c;
	}
	function Install() {
		$c = $this->renderMenu(array(
			'Check System Compatibility ['.Response::getInstance()->writeOkNo($this->lastCheck,true).']',
			 'Install CGAF',
			 'Write Configuration'
			 ));
			 $install = Installer::getInstance('cgaf',CGAF_PATH);
			 switch ($c) {
			 	case '0':
			 		$this->lastCheck =  $install->checkCompat(true);
			 		break;
			 	case '1':
			 		$install->Render();
			 	case '2':
			 		$configs = CGAF::geConfiguration();
			 		$configs->setConfig('installed',true);
			 		$configs = $configs->toPHPConfig();
			 		$path = CGAF_PATH.'config.php';
			 		Utils::backupFile($path);
			 		rw('configuration writed to '.$path);
			 	case 'x':
			 		return;
			 	default:
			 		$this->Install();
			 }
	}
	function writeAt($x,$y,$s) {
		return Response::writeAt($x,$y,$s);
	}
	function scolor($s,$color) {
		return Response::getInstance()->writeColor($s,$color,null,null,true,true);
	}
	function configurecgaf($group=null) {
		static $mselect;
		if (!$group) {
			$mselect = null;
		}
		if (!$group) {
			$menus  = $this->_cgafConfig->getConfigGroups();
			$menus[] = array('title'=>'Configure by config Namespace','value'=>'___direct');
		}else{
			$m = $this->getConfig($group);
			if (is_array($m)) {
				$menus  = array_keys($m);
			}
		}
		if ($menus) {
			$c = $this->renderMenu($menus);
			if ($c == 'x') {
				if ($group == null){
					return;
				}
				$ngroup =  String::FromLastPos($group,',');
				if ($ngroup === $group) {
					$ngroup = null;
				}
				return $this->configurecgaf($ngroup);
			}
			$nc = $c == '___direct' ? '___direct' :($group ? $group .'.' : '').$menus[$c];
			if ($nc == '___direct') {
				$nc =  $this->getString('Enter config Namespace (ex:System) : ');
			}
			if (!$nc) {
				return;
			}
			$cfgs = $this->getConfig($nc);
			if (!is_array($cfgs)) {
				rw('Configuration for '. $this->scolor($nc,'green') );
				rw('Current Value : '.$cfgs);
				rw('Info :');
				rw("\t".__('configs.'.$nc.'.title'));
				rw('Enter New Value (leave blank for use existing value)');
				$nval = $this->getString();
				if (!empty($nval)) {
					$this->setConfig($nc,$nval);
				}
				if ($group == null){
					return;
				}
				$ngroup =  String::FromLastPos($group,',');
				if ($ngroup === $group) {
					$ngroup = null;
				}
				return $this->configurecgaf($ngroup);
			}else{
				$this->configurecgaf(($group ? $group .'.' : '').$menus[$c]);
			}
		}
	}
	private function renderAppList($clear=false) {
		static $list;
		if ($clear || ! $list) {
			$list = AppManager::getInstalledApp();
		}
		if (!count($list)) {
			rw('No application installed');
			return;
		}
		$menus = array();
		foreach($list as $app) {
			$menus[] = array('title'=>$app->app_name,'value'=>$app);
		}
		$c =  $this->renderMenu($menus);
		rw($c);
	}
	private function configureApp() {
		$c =  $this->renderMenu(array(
			'List Installed Application',
			'Install Application',
			'Install Uninstalled Application',
			'Remove Application'
			));
			switch ($c) {
				case '0':
					$this->renderAppList(true);
					break;
				case "1":
					$this->InstallApp();
					break;
			}
	}
	private function installApp() {
		$list = AppManager::getNotInstalledApp();
		$ignore = CGAF::getConfig("appignoreinstall");
		foreach($list as $v) {
			if (!in_array($v,$ignore)) {
				rw("Installing ".$v);
				AppManager::install($v);
			}
		}
		Response::Flush();

	}
	function confirm($title,$simple=false,$def=null) {
		if (!$simple) {
			Response::clearBuffer();
			rw($title);
			$c = $this->renderMenu(array('Yes','No'),false,false);
			return $c==0;
		}else{
			while (true) {
				rw($title.' [Y/N]  ? '.$def,false);
				$c = strtolower($this->getString());
				$c =  $c ? $c : $def;
				if ($c ==='y') {
					return true;
				}elseif ($c==='n'){
					return false;
				}elseif ($def && $c === $def){
					return $def;
				}
			}
			return false;
		}


	}
	function Run() {
		$menus =  array(
			'Install CGAF',
			'Configure CGAF',
			'Application',
			'Developer Tools',
			'ReInit CGAF',
			'x'=>'Exit (^c)');
		if (!CGAF_DEBUG && !System::isLinuxCompat()) {
			Response::writeln("some feature may not available for your OS,\n->we recomend to use linux based system specialy UBUNTU");
			if (!$this->confirm('Continue ?',true)) {
				return;
			}
		}
		if (System::isConsole()) {
			//
			while (true) {
				$installed = $this->getConfig('installed');
				$c = $this->renderMenu($menus,true);
				try {
					switch ($c) {
						case '0':
							if ($installed) {
								rw('CGAF Already Installed');
								break;
							}
							$this->Install();
							break;
						case '1':
							if ($installed) {
								break;
							}
							$this->configureCGAF();
							break;
						case '2':
							$this->configureApp();
							break;
						case '4':
							if ($this->confirm('Delete All Database Data')) {
								CGAF::getConnector()->exec('Delete From #__applications');
							}
							break;
						case 'x':
							CGAF::doExit();
							break;
						default:
					}
				}catch(Exception $e){
					rw($e->getMessage());
				}
				$this->getString('press any key when ready...');
			}
		}
		
	}
}

$app = new TCGAFApplication();
//Disable check offline check.. just run application direct
//CGAF::Run($app);
$app->Run();
?>
