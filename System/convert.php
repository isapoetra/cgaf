<?php
defined("CGAF") or die("Restricted Access");

abstract class Convert
{
    public static function toBoolean($o)
    {
        $t = strtolower(gettype($o));
        switch ($t) {
            case 'boolean':
                return $o;
                break;
            case 'number':
                break;
            case 'string':
                switch (strtolower(trim($o))) {
                    case 'no':
                    case '0':
                    case 'false':
                        return false;
                    case '1':
                    case 'true':
                    case 'yes':
                    default:
                        return true;
                }
                break;
        }
        return (boolean)$o;
    }

    public static function toBool($o)
    {
        return self::toBoolean($o);
    }

    public static function toObject($o, &$ref = null, $bindAll = true)
    {
        if ($ref == null) {
            $ref = new stdClass();
        }
        if ($o == null) {
            return $ref;
        } else {
            if (is_array($o) || is_object($o)) {
                $ref = Utils::bindToObject($ref, $o, $bindAll);
            }
        }
        return $ref;
    }

    public static function toXML($o, $settings = null)
    {
        $root = 'root';
        $child = null;
        if (is_array($settings)) {
            $root = isset($settings['root']) ? $settings['root'] : $root;
            $child = isset($settings['child']) ? $settings['child'] : $child;
        }
        $xml = new SimpleXMLElement("<?xml version=\"1.0\"?><{$root}></{$root}>");
        self::addXMLNode($xml, $o, $child);
        return $xml->asXML();
    }

    private static function addXMLNode(&$xml, $o, $child)
    {
        foreach ($o as $k => $v) {
            $k = is_numeric($k) ? ($child ? $child : 'el' . $k) : $k;
            if (is_array($v)) {
                if (substr($k, 0, 1) == '@') {
                    $xml->addAttribute(substr($k, 1), $v);
                } else {
                    //$ch = $xml->addChild ( $k );
                    self::addXMLNode($xml, $v, $k);
                }
            } else {
                if (substr($k, 0, 1) === '@') {
                    $xml->addAttribute(substr($k, 1), $v);
                } else {
                    $xml->addChild($k, $v);
                }
            }
        }
    }

    public static function toString($o, $delim = '')
    {
        if (is_string($o)) {
            return $o;
        }
        if (($o instanceof \System\Collections\Collection) || is_array($o)) {
            $r = array();
            foreach ($o as $k => $v) {
                $r[] = self::toString($v, true);
            }
            return implode($delim, $r);
        } elseif ($o instanceof \IRenderable) {
            return $o->Render(true);
        } elseif (is_object($o)) {
            ppd($o);
        }
        return $o;
    }

    public static function toNumber($o)
    {
        settype($o, 'float');
        return $o;
    }

    public static function toArray($o)
    {
        if ($o == null) return null;
        if (!$o) return array();
        $retval = $o;
        if (is_object($o) || is_array($o)) {
            $retval = array();
            foreach ($o as $k => $v) {
                $tmp = $v;
                if (is_object($v) || is_array($v)) {
                    $tmp = self::toArray($v);
                }
                $retval[$k] = $tmp;
            }
        }
        return $retval;
    }

    public static function Unit($from, $to, $unit)
    {
        $from =strtolower($from);
        $to =strtolower($to);
        if ($from===$to) return $unit;
        $weight_array = array('g', 'oz', 'lb', 'kg');
        $distance_array = array('cm', 'in', 'ft', 'yd', 'm', 'km', 'mi');
        $volume_array = array('l', 'gal');
        $temperature_array = array('F', 'C');

        $w = in_array($from, $weight_array)&&in_array($to, $weight_array);
        $d = in_array($from, $distance_array)&&in_array($to, $distance_array);
        $v = in_array($from, $volume_array)&&in_array($to, $volume_array);
        $m = in_array($from, $temperature_array)&&in_array($to, $temperature_array);

        $c = array('oz g' => $unit*28.35,
            'lb g' => $unit*453.59,
            'kg g' => $unit*1000,
            'g oz' => $unit/28.35,
            'kg oz' => $unit*35.274,
            'lb oz' => $unit*16,
            'g lb' => $unit/453.59,
            'oz lb' => $unit/16,
            'kg lb' => $unit*2.205,
            'g kg' => $unit/1000,
            'oz kg' => $unit/35.274,
            'lb kg' => $unit/2.205,
            'in cm' => $unit*2.54,
            'ft cm' => $unit*30.48,
            'yd cm' => $unit*91.44,
            'mi cm' => $unit*160934,
            'cm in' => $unit/2.54,
            'ft in' => $unit*12,
            'km m' =>$unit*1000,
            'm km' =>$unit/1000,
            'yd in' => $unit*36,
            'mi in' => $unit*63360,
            'cm ft' => $unit/30.48,
            'in ft' => $unit/12,
            'yd ft' => $unit*3,
            'mi ft' => $unit*5280,
            'cm yd' => $unit/91.44,
            'in yd' => $unit/36,
            'ft yd' => $unit/3,
            'mi yd' => $unit*1760,
            'cm mi' => $unit/160934,
            'in mi' => $unit/63360,
            'ft mi' => $unit/5280,
            'yd mi' => $unit/1760,
            'l quarts' => $unit*1.057,
            'l gal' => $unit/3.785,
            'quarts l' => $unit/1.057,
            'quarts gal' => $unit/4,
            'gal l' => $unit*3.785,
            'gal quarts' => $unit*4,
            'C F' => ((9/5)*$unit)+32,
            'F C' => 5/9*($unit-32));

        if($w||$d||$v||$m)
        {
            $x = $c[$from." ".$to];
            return $x;
        }
        else
        {
            echo 'Improper Conversion';
        }
    }
}

?>