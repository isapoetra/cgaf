<?php include "System/constants.php"; $id = ( int ) (isset ( $_REQUEST ['id'] )
? $_REQUEST ['id'] : 0); $f =
dirname(__FILE__).'/protected/content/offline/'.$id.'.html'; $msg = ''; if
(is_file($f)) {
	$msg = file_get_contents($f);
} ?> <html lang="en"> <head> <meta charset="utf-8"> <title>Offline</title> <link
href="assets/bootstrap/css/bootstrap.css" rel="stylesheet"> <link
href="assets/bootstrap/css/bootstrap-responsive.css"
	rel="stylesheet">
<link href="assets/bootstrap/js/google-code-prettify/prettify.css"
	rel="stylesheet">
<link href="assets/css/offline.css" rel="stylesheet"> <!-- Le HTML5 shim, for
IE6-8 support of HTML5 elements --> <!--[if lt IE 9]>
      <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->
</head> <body>
	<div class="navbar navbar-fixed-top">
		<div class="navbar-inner">
			<div class="container">
				<a class="btn btn-navbar" data-toggle="collapse"
					data-target=".nav-collapse"> <span
					class="icon-bar"></span> <span
					class="icon-bar"></span> <span
					class="icon-bar"></span>
				</a> <a class="brand"
				href="/"><span>CGAF</span></a> <div
				class="nav-collapse">
					<ul class="nav"> </ul>
				</div>
			</div>
		</div>
	</div> <div class="container">
		<header class="jumbotron masthead">
			<div class="inner">
				<h1>This site is offline</h1> <div
				class="lead"><?php echo Constants::get ( $id
				);?></div> <a class="btn btn-primary"
				href="/"><i class="icon-home
				icon-white"></i>Home</a>
			</div>
		</header>
	</div> <footer class="footer">
		<address>
			<strong>Cipta Graha Informatika Indonesia</strong><br>
			Jl. Veteran III<br> Ds. Cibedug 02/01 No. 27<br>
			Ciawi-Bogor<br>Jawa-Barat 16760<br><abbr
			title="Phone">P:</abbr> <br /> <abbr
			title="Mail">M</abbr> <a
				href="mailto:isapoetra@gmail.com">isapoetra@gmail.com</a><br>
		</address>

	</footer>
</body> </html>