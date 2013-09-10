<?php
namespace System\Mail;
class MailObject extends \BaseObject
{
    public $to;
    public $subject;
    public $content;
    public $cc;
    public $bcc;
    public $priority = 'high';
    private $_headers = array(
        'MIME-Version' => '1.0',
        'Content-Transfer-Encoding' => '8bit'
    );
    private $_attachments = array();
    private $_app;

    function __construct()
    {
        $this->_app = \AppManager::getInstance();
        $this->setSender($this->getConfig('sender'));
        $this->setSensitivity('Personal');
        $this->setContentType('html');
        $this->setPriority('high');
    }

    function getHeaders()
    {
        return $this->_headers;
    }

    public function setPriority($value)
    {
        $this->priority = $value;
    }

    function getAttachments()
    {
        return $this->_attachments;
    }

    public function setContentType($value)
    {
        switch ($value) {
            case 'text':
                $value = 'plain';
            case 'html':
            case 'plain':
                $this->_headers['Content-Type'] = 'text/' . $value . '; charset=utf-8';
                break;
            default:
                throw new MailException('invalid content type ' . $value);
        }
    }

    public function setSender($o)
    {
        $this->_headers['From'] = $o;
    }

    function setSensitivity($o)
    {
        $this->_headers['Sensitivity'] = $o;
    }

    public function addAttachment($file)
    {
        if (is_array($file)) {
            foreach ($file as $f) {
                $this->addAttachment($f);
            }
            return;
        }
        if (!is_file($file)) {
            throw new MailException('mail.invalidattachment');
        }
        if (!in_array($file, $this->_attachments)) {
            $this->_attachments[] = $file;
        }
    }

    private function getConfig($configName, $default = null)
    {
        $configName = 'mail.' . $configName;
        return $this->_app->getConfig($configName, \CGAF::getConfig($configName, $default));
    }


}