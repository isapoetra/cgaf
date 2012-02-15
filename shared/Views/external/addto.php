<?php
$url = Request::getOrigin();
$site_title = isset($site_title) ? $site_title : "CGAF";
?>
<table style="width: 300px; height: 50px; text-align: left; margin-left: auto; margin-right: auto;">
	<tbody>
		<tr>
			<td style="text-align: center;"><a
					href="http://del.icio.us/post?url=<?php echo $url ?>&title=<?php echo $site_title ?>">
					<img
						src="<?php echo $this->getLiveData('addtothis/delicious.png') ?>"
						alt="Add to Del.cio.us"
						style="border: 0px solid; width: 48px; height: 48px;" />
				</a>
			</td>
			<td style="text-align: center;"><a
					href="<?php echo BASE_URL . '/news/rss/' ?>">
					<img
						src="<?php echo $this->getLiveData('addtothis/feeds.png') ?>"
						alt="RSS Feed"
						style="border: 0px solid; width: 48px; height: 48px;" />
				</a>
			</td>
			<td style="text-align: center;"><a
					href="http://technorati.com/faves?add='.get_permalink().'">
					<img
						src="<?php echo $this->getLiveData('addtothis/technorati.png') ?>"
						alt="Add to Technorati Favorites"
						style="border: 0px solid; width: 48px; height: 48px;" />
				</a>
			</td>
			<td style="text-align: center;"><a
					href="http://www.stumbleupon.com/submit?url='.get_permalink().'&title='.the_title('', '', false).'">
					<img
						src="<?php echo $this->getLiveData('addtothis/stumble.png') ?>"
						alt="Stumble It!"
						style="border: 0px solid; width: 48px; height: 48px;" />
				</a>
			</td>
			<td style="text-align: center;"><a
					href="http://digg.com/submit?phase=2&url='.get_permalink().'">
					<img
						src="<?php echo $this->getLiveData('addtothis/digg.png') ?>"
						alt="Digg It!"
						style="border: 0px solid; width: 48px; height: 48px;" />
				</a>
			</td>
		</tr>
	</tbody>
</table>
