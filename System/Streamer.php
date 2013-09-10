<?php

use FileInfo;
use System\Streamer\FLV;
use System\Web\WebUtils;

final class Streamer
{
    public static function StreamString($string, $fileName = null, $mime = null)
    {
        header_remove('Content-type');
        if ($fileName) {
            header("Content-Disposition: attachment; filename=\"" . basename($fileName) . "\";");
        }
        if (!$mime) {
            $finfo = new FileInfo($fileName);
            $mime = $finfo->Mime;
        }
        header('Content-type: ' . $mime);
        header("Content-Length: " . strlen($string));
        echo $string;
        CGAF::doExit();
    }

    private static function flush_buffers()
    {
        ob_end_flush();
        @ob_flush();
        flush();
        ob_start();
    }

    private static function forceStream($file, $ctype)
    {
        if (!$file || !is_readable($file)) {
            return;
        }
        $size = filesize($file);
        $fileinfo = pathinfo($file);
        //workaround for IE filename bug with multiple periods / multiple dots in filename
        //that adds square brackets to filename - eg. setup.abc.exe becomes setup[1].abc.exe
        $filename = (strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE')) ? preg_replace('/\./', '%2e', $fileinfo['basename'], substr_count($fileinfo['basename'], '.') - 1) : $fileinfo['basename'];
        $file_extension = strtolower($fileinfo['extension']);
        $offset = 0;
        //$length = $filesize;
        $rhead = isset($_SERVER['HTTP_RANGE']) ? $_SERVER['HTTP_RANGE'] : null;
        $range = '';
        if ($rhead) {
            list($size_unit, $range_orig) = explode('=', $rhead, 2);
            if ($size_unit == 'bytes') {
                //multiple ranges could be specified at the same time, but for simplicity only serve the first range
                //http://tools.ietf.org/id/draft-ietf-http-range-retrieval-00.txt
                list($range, $extra_ranges) = explode(',', $range_orig, 2);
            } else {
                $range = '';
            }
            $matches = array();
            $partialContent = true;
        }
        //figure out download piece from range (if set)
        list($seek_start, $seek_end) = explode('-', $range, 2);
        //set start and end based on range (if set), else set defaults
        //also check for invalid ranges.
        $seek_end = (empty($seek_end)) ? ($size - 1) : min(abs(intval($seek_end)), ($size - 1));
        $seek_start = (empty($seek_start) || $seek_end < abs(intval($seek_start))) ? 0 : max(abs(intval($seek_start)), 0);
        //add headers if resumable
        //Only send partial content header if downloading a piece of the file (IE workaround)
        if ($seek_start > 0 || $seek_end < ($size - 1)) {
            header('HTTP/1.1 206 Partial Content', true);
        }
        header_remove('Pragma');
        header_remove('X-Powered-By');
        header_remove('P3P');
        header('Last-Modified:' . gmdate("D, d M Y H:i:s", filemtime($file) . ' GMT'));
        header('Cache-Control:max-age=2419200', true);
        header('Expires:' . gmdate("D, d M Y H:i:s", \DateUtils::dateAdd(time(), '30 day') . ' GMT'));
        header('Content-Type: ' . $ctype, true);
        header('Accept-Ranges: bytes');
        header('Content-Range: bytes ' . $seek_start . '-' . $seek_end . '/' . $size);
        //header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . ($seek_end - $seek_start + 1), true);
        //open the file
        $fp = fopen($file, 'rb');
        //seek to start of missing part
        fseek($fp, $seek_start);
        //start buffered download
        while (!feof($fp)) {
            //reset time limit for big files
            set_time_limit(0);
            print(fread($fp, 1024 * 8));
            flush();
            ob_flush();
        }
        fclose($fp);

        exit();
    }

    public static function Stream($file, $mime = null, $downloadmode = false, $allowCache = true)
    {
        if (!is_file($file)) {
            header('Content-Type: ' . \Utils::getFileMime($file), true);
            header("Content-Length: 0", true);
            CGAF::doExit();
        }

        $ext = Utils::getFileExt($file, false);
        if (!$mime) {
            $finfo = new FileInfo($file);
            $mime = $finfo->Mime;
        }

        $content = null;
        $streammode = false;
        switch ($ext) {
            case 'ttf':
            case 'woff':
                header('Access-Control-Allow-Origin: *');
                break;
            case 'jpg':
            case 'png':
                if ($allowCache) {
                    $valid = 30;
                    header('Cache-Control: public, max-age=' . ($valid * 30 * 24 * 60 * 60));
                    header('Last-Modified:' . gmdate("D, d M Y H:i:s", $finfo->mtime) . ' GMT');
                    header('Expires:' . gmdate("D, d M Y H:i:s", \DateUtils::dateAdd(time(), $valid . ' day')) . ' GMT');
                }
                break;
            case 'css':
                if (!$downloadmode) {
                    if (strpos($file, '.min.css') === false) {
                        $content = WebUtils::parseCSS($file, true, FALSE);
                    }
                }
                break;
            case 'png':
                break;
            case 'webm':
            case 'mp4':
            case 'mpg':
                $mime = "video/" . $ext;
                $streammode = true;
                break;
            case 'flv':
                $streamer = new FLV($file);
                $streamer->stream();
                CGAF::doExit();
                return;
                break;
            default:
                break;
        }
        $fsize = filesize($file);
        //\Response::destroy();
        if ($content) {
            header('Content-Type: ' . $mime, true);
            echo $content;
        } else {
            if ($streammode) {
                error_reporting(0);
                self::forceStream($file, $mime);
            } else {
                header('Content-Type: ' . $mime, true);
                header("Content-Length: " . $fsize, true);
                readfile($file);
            }
        }
        if ($downloadmode) {
            header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        }
        exit();
    }

    public static function Render($file)
    {
        return self::Stream($file);
    }
}
