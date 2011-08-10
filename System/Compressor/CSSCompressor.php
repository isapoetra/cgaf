<?php
namespace System\Compressor;
use System\Compressor\CSS\CommentPreserver;
use System\Compressor\CSS\UriRewriter;

class CSSCompressor extends AbstractCompressor {
	private $_content;
	private $_prepared = false;
	protected $_inHack = false;
	// Getter
	function getPreserveComment() {
		return $this->_config->getConfig('preserveComment');
	}
	protected function getDefaultConfigs() {
		return array(
			'preserveComment' => false);
	}

	private function _removeComment() {
		$this->_content = CommentPreserver::process($this->_content, array($this, '_process'), array($this->_config));
	}
	private function _prepare() {
		if ($this->_prepared) {
			return;
		}
		$this->_prepared = true;
		if (!$this->preserveComment) {
			$this->_removeComment();
		}
	}
	protected function _commentCB($m) {
		$hasSurroundingWs = (trim($m[0]) !== $m[1]);
		$m = $m[1];
		// $m is the comment content w/o the surrounding tokens,
		// but the return value will replace the entire comment.
		if ($m === 'keep') {
			return '/**/';
		}
		if ($m === '" "') {
			// component of http://tantek.com/CSS/Examples/midpass.html
			return '/*" "*/';
		}
		if (preg_match('@";\\}\\s*\\}/\\*\\s+@', $m)) {
			// component of http://tantek.com/CSS/Examples/midpass.html
			return '/*";}}/* */';
		}
		if ($this->_inHack) {
			// inversion: feeding only to one browser
			if (preg_match(
					'@
	                    ^/               # comment started like /*/
	                    \\s*
	                    (\\S[\\s\\S]+?)  # has at least some non-ws content
	                    \\s*
	                    /\\*             # ends like /*/ or /**/
	                @x', $m, $n)) {
				// end hack mode after this comment, but preserve the hack and comment content
				$this->_inHack = false;
				return "/*/{$n[1]}/**/";
			}
		}
		if (substr($m, -1) === '\\') {
			// comment ends like \*/
			// begin hack mode and preserve hack
			$this->_inHack = true;
			return '/*\\*/';
		}
		if ($m !== '' && $m[0] === '/') {
			// comment looks like /*/ foo */
			// begin hack mode and preserve hack
			$this->_inHack = true;
			return '/*/*/';
		}
		if ($this->_inHack) {
			// a regular comment ends hack mode but should be preserved
			$this->_inHack = false;
			return '/**/';
		}
		// Issue 107: if there's any surrounding whitespace, it may be important, so
		// replace the comment with a single space
		return $hasSurroundingWs // remove all other comments
 ? ' ' : '';
	}
	/**
	 * Replace what looks like a set of selectors
	 *
	 * @param array $m regex matches
	 *
	 * @return string
	 */
	protected function _selectorsCB($m) {
		// remove ws around the combinators
		return preg_replace('/\\s*([,>+~])\\s*/', '$1', $m[0]);
	}
	/**
	 * Process a font-family listing and return a replacement
	 *
	 * @param array $m regex matches
	 *
	 * @return string
	 */
	protected function _fontFamilyCB($m) {
		$m[1] = preg_replace(
				'/
	                \\s*
	                (
	                    "[^"]+"      # 1 = family in double qutoes
	                    |\'[^\']+\'  # or 1 = family in single quotes
	                    |[\\w\\-]+   # or 1 = unquoted family
	                )
	                \\s*
	            /x', '$1', $m[1]);
		return 'font-family:' . $m[1] . $m[2];
	}
	function _process($css) {
		$css = str_replace("\r\n", "\n", $css);
		// preserve empty comment after '>'
		// http://www.webdevout.net/css-hacks#in_css-selectors
		$css = preg_replace('@>/\\*\\s*\\*/@', '>/*keep*/', $css);

		// preserve empty comment between property and value
		// http://css-discuss.incutio.com/?page=BoxModelHack
		$css = preg_replace('@/\\*\\s*\\*/\\s*:@', '/*keep*/:', $css);
		$css = preg_replace('@:\\s*/\\*\\s*\\*/@', ':/*keep*/', $css);

		// apply callback to all valid comments (and strip out surrounding ws
		$css = preg_replace_callback('@\\s*/\\*([\\s\\S]*?)\\*/\\s*@', array($this, '_commentCB'), $css);

		// remove ws around { } and last semicolon in declaration block
		$css = preg_replace('/\\s*{\\s*/', '{', $css);
		$css = preg_replace('/;?\\s*}\\s*/', '}', $css);

		// remove ws surrounding semicolons
		$css = preg_replace('/\\s*;\\s*/', ';', $css);

		// remove ws around urls
		$css = preg_replace(
				'/
				                url\\(      # url(
				                \\s*
				                ([^\\)]+?)  # 1 = the URL (really just a bunch of non right parenthesis)
				                \\s*
				                \\)         # )
				            /x', 'url($1)', $css);

		// remove ws between rules and colons
		$css = preg_replace(
				'/
				                \\s*
				                ([{;])              # 1 = beginning of block or rule separator
				                \\s*
				                ([\\*_]?[\\w\\-]+)  # 2 = property (and maybe IE filter)
				                \\s*
				                :
				                \\s*
				                (\\b|[#\'"])        # 3 = first character of a value
				            /x', '$1$2:$3', $css);

		// remove ws in selectors
		$css = preg_replace_callback(
				'/
				                (?:              # non-capture
				                    \\s*
				                    [^~>+,\\s]+  # selector part
				                    \\s*
				                    [,>+~]       # combinators
				                )+
				                \\s*
				                [^~>+,\\s]+      # selector part
				                {                # open declaration block
				            /x', array($this, '_selectorsCB'), $css);

		// minimize hex colors
		$css = preg_replace('/([^=])#([a-f\\d])\\2([a-f\\d])\\3([a-f\\d])\\4([\\s;\\}])/i', '$1#$2$3$4$5', $css);

		// remove spaces between font families
		$css = preg_replace_callback('/font-family:([^;}]+)([;}])/', array($this, '_fontFamilyCB'), $css);

		$css = preg_replace('/@import\\s+url/', '@import url', $css);

		// replace any ws involving newlines with a single newline
		$css = preg_replace('/[ \\t]*\\n+\\s*/', "\n", $css);

		// separate common descendent selectors w/ newlines (to limit line lengths)
		$css = preg_replace('/([\\w#\\.\\*]+)\\s+([\\w#\\.\\*]+){/', "$1\n$2{", $css);

		// Use newline after 1st numeric value (to limit line lengths).
		$css = preg_replace('/
				            ((?:padding|margin|border|outline):\\d+(?:px|em)?) # 1 = prop : 1st numeric value
				            \\s+
				            /x', "$1\n", $css);

		// prevent triggering IE6 bug: http://www.crankygeek.com/ie6pebug/
		$css = preg_replace('/:first-l(etter|ine)\\{/', ':first-l$1 {', $css);

		return trim($css);
	}
	function _processUriCB($css) {

		// $m matched either '/@import\\s+([\'"])(.*?)[\'"]/' or '/url\\(\\s*([^\\)\\s]+)\\s*\\)/'
		$isImport = ($m[0][0] === '@');
		// determine URI and the quote character (if any)
		if ($isImport) {
			$quoteChar = $m[1];
			$uri = $m[2];
		} else {
			// $m[1] is either quoted or not
			$quoteChar = ($m[1][0] === "'" || $m[1][0] === '"') ? $m[1][0] : '';
			$uri = ($quoteChar === '') ? $m[1] : substr($m[1], 1, strlen($m[1]) - 2);
		}
		// analyze URI
		if ('/' !== $uri[0] // root-relative
 && false === strpos($uri, '//') // protocol (non-data)
 && 0 !== strpos($uri, 'data:') // data protocol
		) {
			// URI is file-relative: rewrite depending on options
			$uri = (self::$_prependPath !== null) ? (self::$_prependPath . $uri) : self::rewriteRelative($uri, self::$_currentDir, self::$_docRoot, self::$_symlinks);
		}
		return $isImport ? "@import {$quoteChar}{$uri}{$quoteChar}" : "url({$quoteChar}{$uri}{$quoteChar})";

	}
	function compressString($string) {
		$this->_content = $string;
		$this->_prepare();
		$css = $this->_process($this->_content);
		$css = UriRewriter::rewrite($css, $this->_currentPath);
		return $css;
	}
}
