<?php
include 'cgaf.php';

if (!CGAF::Initialize()) {
	throw new System\Exceptions\SystemException('Initializing framork');
}