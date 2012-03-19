<?php
defined("CGAF") or die("Restricted Access");
$buff = null;
global $args;
$args = $args ? $args : func_get_args();

if (CGAF_DEBUG) {
	$bt = debug_backtrace();
	if (!CGAF_DEBUG) {
		array_shift($bt);
		array_shift($bt);
	}
	if ($bt[0]["function"] == "exception_handler") {
		if ($bt[0]["args"][0] instanceof Exception) {
			$bt = $bt[0]["args"][0]->getTrace();
		}
	}
}
?>
<html>
<head>
<title>CGAF::Error</title>
<style type="text/css">
body {
	background-color: #999999;
}

#wrapper {
	margin: 0 auto;
	min-height: 100%;
	margin: 20px;
	-moz-background-clip: border;
	-moz-background-inline-policy: continuous;
	-moz-background-origin: padding;
	-moz-border-radius-bottomleft: 0.5em;
	-moz-border-radius-bottomright: 0.5em;
	-moz-border-radius-topleft: 0.5em;
	-moz-border-radius-topright: 0.5em;
	background: #FAFAFA none repeat scroll 0 0;
	border: 1px solid #AAAAAA;
}

.error {
	display: block;
	text-align: left;
	position: static;
	width: 100%;
	overflow: auto;
}

.error .title {
	background-color: #CC3300;
	font-weight: bold;
	text-align: center;
	color: #FF0000;
	background: #FAFAFA none repeat scroll 0 0;
	-moz-border-radius-bottomleft: 0.2em;
	-moz-border-radius-bottomright: 0.2em;
	-moz-border-radius-topleft: 0.2em;
	-moz-border-radius-topright: 0.2em;
}

.error .messages {
	display: block;
	border: 1px solid gray;
	background-color: #FFFFFF;
	margin: 5px;
}

.error .row,.error span {
	display: block;
}

.error span {
	border: 1px solid gray;
	display: block;
}

.error .backtrace {
	font-family: verdana;
	font-size: small;
}

.error .backtrace .row {
	
}

.error .backtrace .args {
	color: red;
	border: none;
	padding-left: 0.5em;
	overflow: auto;
	max-height: 150px;
}

.error .backtrace .args span {
	border: none;
}

.error .backtrace .args ul {
	list-style: none;
	color: black;
}

.error .backtrace .class .file {
	border: none;
	padding-left: 0.5em;
	display: inline;
	color: gray;
}

.error span.title {
	background-color: blue;
	color: #999999;
}

.debug {
	display: block;
	border: 1px solid gray;
	margin: 1px;
	text-align: left;
	background-color: gainsboro;
	font-size: x-small;
}

.debug .warning {
	background: yellow url(/Data/images/debug_warning.png) no-repeat;
}

.debug .notice {
	background: gainsboro url(/Data/images/debug_notice.png) no-repeat;
}

.debug span {
	display: block;
	line-height: 20px;
	padding-left: 20px;
}

.debug .error {
	background-color: red;
}

body {
	margin-left: 0px;
	margin-top: 0px;
	margin-right: 0px;
	margin-bottom: 0px;
}
</style>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
</head>
<body>
	<div id="wrapper">
		<div class="error">
			<div class="title">
				<div>
					Uncaught Exception
					<?php
					if (class_exists('AppManager', false)) {
						echo AppManager::isAppStarted() ? AppManager::getInstance()->getAppName() : "";
					}
					?>
				</div>
			</div>
			<div class="messages">

				<?php
				echo self::format($args);
				?>
			</div>
			<div>
				<a href="<?php echo BASE_URL ?>">Home</a>
				<a href="<?php echo BASE_URL.'?__appId='.\CGAF::APP_ID ?>">Home</a>
			</div>
			<?php
			if (CGAF_DEBUG) {
				?>
			<div class="backtrace">
				<span class="title">Back Trace</span>
				<?php
				echo self::print_backtrace($bt, true)
				?>
			</div>
			<span>Buffer</span>
			<div class="buffer">
				<?php
				echo pp($buff ? $buff : 'Empty Buffer', true)
				?>
			</div>
			<?php
			}
			?>

		</div>
		<?php
		if (CGAF_DEBUG) {
			?>
		<div id="debugframe" class="debug" title="Debug Console">
			<?php
			echo Logger::WriteDebug(Logger::Flush(true));
			?>
		</div>
		<?php
		}
		?>
	</div>
</body>
</html>
