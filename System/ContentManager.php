<?php
/**
 * User: isapoetra
 * Date: 7/17/13
 * Time: 10:39 PM
 */

class ContentManager
{

    public static function removeAppContent($position = null)
    {
        $app = \AppManager::getInstance();
        $m = $app->getModel('contents');
        $m->where('app_id=' . $m->quote($app->getAppId()));
        if ($position) {
            $m->where('position=' . $m->quote('position'));
        }
        $m->delete();
    }

    public static function removeControllerContent($controller = null, $position = null)
    {
        $app = \AppManager::getInstance();
        $m = $app->getModel('contents');
        $m->where('app_id=' . $m->quote($app->getAppId()));
        $m->where('content_controller=' . $m->quote($controller));
        if ($position) {
            $m->where('position=' . $m->quote('position'));
        }
        $m->delete();
    }

    public static function addContent($content)
    {
        $app = \AppManager::getInstance();
        $m = $app->getModel('contents');
        $m->newData();
        if (!$m->app_id) $m->app_id = $app->getAppId();
        foreach ($content as $k => $v) {
            $m->$k = $v;
        }
        if ($m->state === null) {
            $m->state = 1;
        }
        if (!$m->content_id) {
            $max = $app->getModel('contents', true)
                ->clear('select')
                ->select('max(content_id)+1', 'max', true)
                ->Where('app_id=' . $m->quote($app->getAppId()))
                ->loadObject();
            $m->content_id = isset($max->max) ? $max->max : 1;
        }
        try {
            $id = $m->store(true);
        } catch (UnchangedDataException $e) {
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public static function add($contents)
    {
        foreach ($contents as $content) {
            self::addContent($content);
        }

    }
}