<?php
namespace System\Web\UI\JQ;
use System\JSON\JSON;
use System\Session\Session;
use System\Web\JS\JSFunc;

/**
 * Handle file uploads via XMLHttpRequest
 */
class qqUploadedFileXhr
{
    /**
     * Save the file to the specified path
     * @return boolean TRUE on success
     */
    function save($path)
    {
        $input = fopen("php://input", "r");
        $temp = tmpfile();
        $realSize = stream_copy_to_stream($input, $temp);
        fclose($input);

        if ($realSize != $this->getSize()) {
            return false;
        }

        $target = fopen($path, "w");
        fseek($temp, 0, SEEK_SET);
        stream_copy_to_stream($temp, $target);
        fclose($target);

        return true;
    }

    function getName()
    {
        return $_GET['qqfile'];
    }

    function getSize()
    {
        if (isset($_SERVER["CONTENT_LENGTH"])) {
            return (int)$_SERVER["CONTENT_LENGTH"];
        } else {
            throw new Exception('Getting content length is not supported.');
        }
    }
}

/**
 * Handle file uploads via regular form post (uses the $_FILES array)
 */
class qqUploadedFileForm
{
    /**
     * Save the file to the specified path
     * @return boolean TRUE on success
     */
    function save($path)
    {
        if (!move_uploaded_file($_FILES['qqfile']['tmp_name'], $path)) {
            return false;
        }
        return true;
    }

    function getName()
    {
        return $_FILES['qqfile']['name'];
    }

    function getSize()
    {
        return $_FILES['qqfile']['size'];
    }
}

class Upload extends Control
{
    private $_assets = array('cgaf/fileuploader.js', 'cgaf/uploader.js');
    private $_scriptdata = array();
    private $_savePath;
    private $_allowedExtensions = array('png');
    private $_sizeLimit = 10485760;
    private $_destFileName;

    function __construct($id, $action, $savePath, $destFileName = null)
    {
        parent::__construct($id);
        $this->setConfig('action', $action);
        $this->_savePath = $savePath;
        $this->_destFileName = $destFileName;
    }

    function setCSS($css)
    {
        $this->_css = $css;
        return $this;
    }

    function setMulti($value)
    {
        return $this->setConfig('multi', $value);
    }

    function addScriptData($key, $val)
    {
        $this->_scriptdata [$key] = $val;
    }


    private function handleUpload($replaceOldFile = FALSE)
    {
        $uploadDirectory = $this->_savePath;
        if (isset($_GET['qqfile'])) {
            $file = new qqUploadedFileXhr();
        } elseif (isset($_FILES['qqfile'])) {
            $file = new qqUploadedFileForm();
        } else {
            $file = false;
        }

        if (!is_writable($uploadDirectory)) {
            return array('error' => "Server error. Upload directory isn't writable.");
        }

        if (!$file) {
            return array('error' => 'No files were uploaded.');
        }

        $size = $file->getSize();

        if ($size == 0) {
            return array('error' => 'File is empty');
        }

        if ($size > $this->_sizeLimit) {
            return array('error' => 'File is too large');
        }

        $pathinfo = pathinfo($file->getName());
        $filename = $pathinfo['filename'];
        //$filename = md5(uniqid());
        $ext = $pathinfo['extension'];

        if ($this->_allowedExtensions && !in_array(strtolower($ext), $this->_allowedExtensions)) {
            $these = implode(', ', $this->_allowedExtensions);
            return array('error' => 'File has an invalid extension, it should be one of ' . $these . '.');
        }

        if (!$this->_destFileName) {
            /// don't overwrite previous files that were uploaded
            while (file_exists($uploadDirectory . $filename . '.' . $ext)) {
                $filename .= rand(10, 99);
            }
        }

        if ($file->save($uploadDirectory . $this->_destFileName . '.' . $ext)) {
            return array('success' => true);
        } else {
            return array('error' => 'Could not save uploaded file.' .
            'The upload was cancelled, or server error encountered');
        }

    }

    private function save()
    {
        $input = fopen("php://input", "r");
        $temp = tmpfile();
        $realSize = stream_copy_to_stream($input, $temp);
        fclose($input);

        if ($realSize != $this->getSize()) {
            return false;
        }

        $target = fopen($this->_savePath, "w");
        fseek($temp, 0, SEEK_SET);
        stream_copy_to_stream($temp, $target);
        fclose($target);
    }

    function renderJSON($return = true)
    {
        return $this->handleUpload();
    }

    function getContainerId()
    {
        return $this->getId() . 'container';
    }

    function RenderScript($return = false)
    {
        $configs = $this->_configs;
        $configs->setConfig('element', new JSFunc('document.getElementById(\'' . $this->getContainerId() . '\')'));
        $configs->setConfig('allowedExtensions', $this->_allowedExtensions);
        $configs = JSON::encodeConfig($this->_configs);
        $js = <<< EOT
var uploader = new qq.FileUploader($configs);
EOT;
        $this->getAppOwner()->addClientScript($js);

        $retval = '<div id="' . $this->getContainerId() . '"></div>';
        return $retval;
    }
}