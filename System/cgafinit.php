<?php
include 'cgaf.php';
if (!CGAF::Initialize()) {
	throw new SystemException('Initializing framork');
} 