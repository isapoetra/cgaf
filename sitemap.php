<?php try {
	require 'cgafinit.php'; function writexmltag(&$s, $k, $v) {
		foreach ($v as $kk => $vv) {
			$s[] = "<$k:$kk>"; if (is_string($vv)) {
				$s[] = $vv;
			} else {
				writexmltag($s, $k, $vv);
			} $s[] = "</$k:$kk>";
		}
	} $s = array('<?xml version="1.0" encoding="UTF-8"?>');
	$now = new \DateTime(); $now = $now->format('Y-m-d 00:00:00');
	$d = \DateTime::createFromFormat('Y-m-d H:i:s', $now); if (!\Request::get('q')) {
		$s[] = '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
		$s[] = ' <sitemap>'; $s[] = ' <loc>' . BASE_URL .
		'sitemap.php?q=cgaf</loc>';
		//$s[] = ' <lastmod>' . $d->format(\DateTime::ISO8601) . '</lastmod>';
		$s[] = ' </sitemap>'; $s[] = '</sitemapindex>';
	} else {
		$q = \Request::get('q'); $sitemaps = array(
				'cgaf' => array(
						'url' => array(
								'loc' => \URLHelper::add(BASE_URL, 'news', '__appId=__cgaf'),
								'news' => array(
										array(
												'publication' => array(
														'name' => \CGAF::getConfig('cgaf.title'), 'language'
														=> 'en'),
												'title' => \CGAF::getConfig('cgaf.title'), 'keywords' =>
												\CGAF::getConfig('cgaf.tags'), 'publication_date' =>
												$d->format('Y-m-d'), 'genres' =>
												'PressRelease,Blog,UserGenerated')))));
		$sitemaps = isset($sitemaps[$q]) ? $sitemaps[$q] : $sitemaps['cgaf']; $s =
		array(
				'<?xml version="1.0" encoding="UTF-8"?>');
		$s[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
		xmlns:image="http://www.sitemaps.org/schemas/sitemap-image/1.1"
		xmlns:video="http://www.sitemaps.org/schemas/sitemap-video/1.1"
		xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">'; foreach
		($sitemaps as $k => $p) {
			$s[] = '<' . $k . '>'; foreach ($p as $kk => $vv) {
				switch ($kk) { case 'news':
					$s[] = "<$kk:$kk>"; foreach ($vv as $vvv) {
						writexmltag($s, $kk, $vvv);
					} $s[] = "</$kk:$kk>"; break;
				default:
					$s[] = '<' . $kk . '>' . $vv . '</' . $kk . '>'; break;
				}
			} $s[] = '</' . $k . '>';
		} $s[] = '</urlset>';
	} \Streamer::StreamString(implode(PHP_EOL, $s), null, 'text/xml');
} catch (\Exception $e) {
	ppd($e);
} ?>
