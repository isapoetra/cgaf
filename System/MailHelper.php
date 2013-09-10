<?php

use System\DB\Table;
use System\Exceptions\SystemException;
use System\Mail\MailObject;

abstract class MailTransport
{
    private static $_instance = array();

    /**
     *
     * @param unknown_type $transport
     * @return \System\Mail\Transport\AbstractTransport
     */
    public static function getInstance($transport = null)
    {
        $transport = $transport ? $transport : MailHelper::getConfig('engine', 'smtp');
        if (!isset(self::$_instance[$transport])) {
            $c = 'System\\Mail\\Transport\\' . $transport;
            self::$_instance[$transport] = new $c;
        }
        return self::$_instance[$transport];
    }
}

class MailException extends SystemException
{

}

class MailHelper
{
    public static function getConfigs($configName, $default = null)
    {
        $configName = 'mail.' . $configName;
        $retval = array();
        $iconfig = \AppManager::getInstance()->getConfigs($configName, array());
        $cconfig = \CGAF::getConfigs($configName);
        return array_merge($cconfig, $iconfig);

    }

    public static function getConfig($configName, $default = null)
    {
        $configName = 'mail.' . $configName;
        return \AppManager::getInstance()->getConfig($configName, \CGAF::getConfig($configName, $default));

    }

    private static function findTemplate($template, $ext = 'html')
    {
        $app = AppManager::getInstance();
        $lc = $app->getLocale()->getLocale();
        $sfile = array(
            $template . '-' . $lc,
            $template
        );
        $spath = array($app->getInternalData("template/email/"),
            \CGAF::getInternalStorage('template/email/', false, false)
        );
        foreach ($spath as $p) {
            foreach ($sfile as $f) {
                $fs = $p . $f . '.' . $ext;
                if (is_file($fs)) {
                    return $fs;
                }
            }
        }
    }

    public static function send($to, $subject, $message, $attachmens = array())
    {
        $e = new MailObject();
        $e->to = $to;
        $e->content = $message;
        $e->subject = $subject;
        $e->addAttachment($attachmens);
        if (!$ok = MailTransport::getInstance()->send($e)) {
            throw new MailException('mail.senderror');
        }
    }

    public static function sendMail($m, $template, $subject, $userowner = null)
    {

        $app = AppManager::getInstance();

        $tpl = self::findTemplate($template);

        if ($tpl) {
            $o = new stdClass ();
            if ($m instanceof Table) {
                $arr = $m->getFields(true, true, false);
                $o = Utils::bindToObject($o, $arr, true);
            } else if (is_array($m)) {
                $o = Utils::bindToObject($o, $m, true);
            } elseif (is_object($m)) {
                $o = $m;
            }

            $mail = null;
            if (isset($o->user_email) || isset($o->user_name)) {
                $mail = $o->user_email ? $o->user_email : $o->user_name;
            }
            $o->title = __($subject);
            $o->base_url = BASE_URL;
            $msg = Utils::parseDBTemplate(file_get_contents($tpl), $o);

            if ($userowner) {
                $app->notifyUser($userowner, $o->title, $msg);
            }
            if (!\Utils::isEmail($mail)) {
                throw new SystemException('error.invalidemail');
            }

            $e = new MailObject();
            $e->to = $mail;
            $e->content = $msg;
            $e->subject = $subject;
            if (!$ok = MailTransport::getInstance()->send($e)) {
                throw new MailException('mail.senderror');
            }
            return $ok;
        } else {
            throw new SystemException ('error.mail.template.notfound', $template);
        }
    }

}

?>