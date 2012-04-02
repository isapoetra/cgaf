<?php
namespace System\Documents;
interface IDocument extends  \IRenderable{
	function loadFile($f);
}