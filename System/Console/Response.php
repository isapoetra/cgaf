<?php
if (!defined("CGAF")) die("Restricted Access");

class ConsoleResponse extends TResponse {
	private $_colors = array (
	    'color' => array(
	            'black'  => 30, 
	            'red'    => 31,
	            'green'  => 32,
	            'brown'  => 33,
	            'blue'   => 34,
	            'purple' => 35,
	            'cyan'   => 36,
	            'grey'   => 37,
	            'yellow' => 33
	),
	    'style' => array(
	            'normal'     => 0,
	            'bold'       => 1,
	            'light'      => 1,
	            'underscore' => 4,
	            'underline'  => 4,
	            'blink'      => 5,
	            'inverse'    => 6,
	            'hidden'     => 8,
	            'concealed'  => 8
	),
	    'background' => array(
	            'black'  => 40,
	            'red'    => 41,
	            'green'  => 42,
	            'brown'  => 43,
	            'yellow' => 43,
	            'blue'   => 44,
	            'purple' => 45,
	            'cyan'   => 46,
	            'grey'   => 47
	)
	);
	function __construct () {
		parent::__construct(false);
	}
	function writeLn($t) {
		return $this->write($t."\n");
	}
	function clearBuffer() {
		parent::clearBuffer();
		if (System::isWindows()){
			Utils::sysexec('clear');
		}elseif (System::isLinuxCompat()) {
			Utils::sysexec('clear');
		}
	}
	private function parseColor($fore,$back=null,$style=null) {
		//TODO Fix
		if (!System::isLinuxCompat()) {
			return  '';
		}
		if ($fore == 'reset') {
			return "\033[0m";
		}
		$code = array();
        if ($fore) {
            $code[] = $this->_colors['color'][$fore];
        }
        if ($style) {
            $code[] = $this->_colors['style'][$style];
        }
        if ($back) {
            $code[] = $this->_colors['background'][$back];
        }
        if (empty($code)) {
            $code[] = 0;
        }
        $code = implode(';', $code);
        return "\033[{$code}m";
	}
	function WriteColor($msg,$fore=null,$back=null,$style=null,$resetend=true,$return=false,$newL=true) {
		$r = $this->parseColor($fore,$back,$style).$msg.($resetend ? $this->parseColor('reset') :'');
		if (!$return) {
			if ($newL) {
				$this->writeln($r);
			}else{
				$this->write($r);
			}
		}
		return $r;
	}
	function writeOkNo($val,$return=false) {
		$r = $val ?  $this->WriteColor('Ok','green',null,null,true,true) : $this->WriteColor('Fail','red',null,'bold',true,true);
		if (!$return) {
			$this->write($r);
		}
		return $r;
	}
	function Redirect($url=null) {
		echo $url;
	}
}
