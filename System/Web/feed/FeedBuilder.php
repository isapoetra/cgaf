<?php
namespace System\Web\Feed;

use System\Exceptions\SystemException;

abstract class FeedBuilder
{
    private function __construct()
    {
    }

    public static function getInstance($type = 'rss')
    {
        $c = 'System\\Web\\Feed\\' . $type;
        $c = new $c;
        return $c;
    }

    public static function build($type, $data)
    {

        $instance = self::getInstance($type);
        if ($instance) {
            $instance->setData($data);
            $instance->render(false);
            \CGAF::doExit();
        } else {
            throw new SystemException('Unknown Feed Type $type');
        }
    }
}