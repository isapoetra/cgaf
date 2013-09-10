<?php
namespace System\Console;
class TTY extends \BaseObject
{
    private $_tty;

    function __construct()
    {
        if (substr(PHP_OS, 0, 3) == "WIN") {
            $this->_tty = fOpen("\con", "rb");
        } else {
            if (!($this->_tty = fOpen("/dev/tty", "r"))) {
                $this->_tty = fOpen("php://stdin", "r");
            }
        }
    }

    public function __destruct()
    {
        @fclose($this->_tty);
    }

    public function ask($title)
    {
        //$t = $title.' [Y/N]';
        $r = '';
        while ($r != 'Y' && $r != 'N') {
            $t = $title . ' [Y/N]';
            $r = strtoupper($this->readChar($t));
        }
        return $r === 'Y';
    }

    public function readLn($title = '', $allowEmpty = true, $length = 1024)
    {
        echo $title;
        $read = trim(fGets($this->_tty, $length));
        if (!$allowEmpty) {
            while (!$read) {
                echo PHP_EOL . $title;
                $read = trim(fGets($this->_tty, $length));

            }
        }
        return $read;
    }

    public function readChar($title = '')
    {
        echo $title;
        return trim(fgetc($this->_tty));
    }

    public function renderMenu($items, $callback, $quitChar)
    {
        do {
            $this->clear();
            foreach ($items as $k => $menu) {
                echo $k . '. ' . $menu['title'] . PHP_EOL;
            }
            echo "Press [$quitChar] to exit" . PHP_EOL;
            $r = $this->readLn();
            if (array_key_exists($r, $items)) {
                $menu = $items[$r];
                switch ($menu['type']) {
                    default:
                        $action = $menu['action'];
                        $callback->$action($r);
                        break;
                }
            }
        } while ($r != $quitChar);
    }

    public function clear()
    {
        if (substr(PHP_OS, 0, 3) == "WIN") {
            passthru('cls');
        } else {
            passthru('clear');
        }
    }
}