<?php
/**
 * User: isapoetra
 * Date: 7/17/13
 * Time: 10:39 PM
 */

class ContentManager {

    public static function removeAppContent($position=null)
    {
        $app =\AppManager::getInstance();
        $m = $app->getModel('contents');
        $m->where('app_id='.$m->quote($app->getAppId()));
        if ($position) {
            $m->where('position='.$m->quote('position'));
        }
        $m->delete();
    }
    public static function addContent($content) {
        $app = \AppManager::getInstance();
        $m = $app->getModel('contents');
        $m->newData();
        if (!$m->app_id) $m->app_id = $app->getAppId();
        foreach($content as $k=>$v) {
            $m->$k =$v;
        }
        try {
            $id = $m->store(true);
        } catch (UnchangedDataException $e) {
        } catch (\Exception $e) {
            throw $e;
        }
    }
    public  static  function add($contents) {
        foreach($contents as $content) {
            self::addContent($content);
        }

    }
}