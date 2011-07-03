<?php
CGAF::loadLibs('pdf');
abstract class PDFUtils  {
	function __construct() {
		throw new UnimplementedException();
	}
	public static function search($texttosearch,$source,$destpath,$configs=array(),$parse=false) {
		$destname = $destpath.DS. (isset($configs['basename']) ? $configs['basename'].'.xml' : 'page.xml');		
		if (!is_file($destname)) {
			$destname = self::convertTo($source,$destpath,'xml',$configs);
		}
		$texttosearch  =addslashes($texttosearch);
		$retval = array();
		$xml = simplexml_load_file($destname);
		foreach($xml as $page) {
			$founds = array();
			$attr =$page->attributes();	
			foreach($page as $text) {
				$tag = $text->getName();
				$attrtext = $text->attributes();
				switch ($tag) {
					case 'text':
						$txt =trim((string)$text);
						$matches =null;
						preg_match_all('/('.$texttosearch.')+/i',$txt,$matches,PREG_OFFSET_CAPTURE);
						if ($matches[0]) {
							$r = array(
								'text'=>$txt,
								'match' => $matches[0]
							);
							$retval[(string)$page['number']][] = $r;					
						}
						
						break;
				}
				
			}
		}
		if ($parse) {
			return self::parseSearchResult($texttosearch,$retval);
		}
		return $retval;
	}
	private static function parseSearchResult($stext,$sr) {		
		$r = '<ul>';
		foreach($sr as $p=>$found) {
			$r .= '<li>'
			.'<span>Page :'.$p.'</span>'
			.'<ul class="result">';
			foreach($found as $v) {
				$r .= '<li>';
				$r .= preg_replace('/('.$stext.')/i',"<b><font style='color:white; background-color:blue;'>" . $stext . "</font></b>",$v[text]);
				$r.='</li>';
			}
			$r.='</ul>'
			.'</li>';
		}
		$r .= '</ul>';
		return $r;
	}
	public static function convertTo($source,$destpath,$destFormat,$configs=array()) {
		CGAF::Using('libs.pdf.converter.'.$destFormat);
		$className = 'TPDFConverter'.$destFormat;
		$c = new $className($source);
		$c->setDestPath($destpath);
		$c->setConfig($configs);
		return $c->convert();
	}
	public static function generateThumb($pdffile,$destpath,$config=array(
		'basename'=>'thumb',
		'overwrite'=>false	
	)) {
		if (!is_dir($destpath)) {
			throw new SystemException('invalid destination path');
		}
		
		$pdffile =  Utils::QuoteFileName($pdffile);
		$spage = isset($config['startpage']) ? $config['startpage'] :1;
		$lpage = isset($config['endpage']) ? $config['endpage'] :1;
		$thumbsize = isset($config['thumbsize']) ? $config['thumbsize'] :200;
		$basename =isset($config['basename']) ? $config['basename'] : null;
		$ext =isset($config['extension']) ? $config['extension'] : 'png';
		$overwrite = isset($config['overwrite']) ? $config['overwrite'] : false;
		$retval = array();
		for($i=$spage ;$i<=$lpage;$i++) {
			$rdest = $destpath.DS.($basename ? $basename.'-' : '').$i.'.'.$ext;
			$destfile = Utils::QuoteFileName($rdest);
			if (!is_file($destfile) || $overwrite) { 
				$cmd = 'pdftoppm -f '.$i .' -l '. $i .' '.$pdffile.' | convert -thumbnail '.$thumbsize .' - '.$destfile;
				$r = Utils::sysexec($cmd,null,false);
				if ($r === 0) {
					$retval[$i] = $rdest;
				}
			}else{
				$retval[$i] = $rdest;
			}
		}
		return $retval;
		
	}
}