<?php
$swf = new YahooPingBox($this->getAppOwner()->getConfig('pingbox.id'));
echo $swf->Render(true);
