<?php
namespace System\Documents;
class VCARDTelephoneParams
{
}

class VCARDAddressInfo
{
    public $addrType = 'work';
    public $address = null;
    public $pobox;
    public $ext;
    public $street;
    public $locality;
    public $region;
    public $code;
    public $country;

    function toXML($root)
    {
        $params = $root->addChild('parameters');
        $this->addChild($params, 'type', $this->addrType);
        $this->addChild($params, 'label', $this->address);
        $this->addChild($root, 'pobox', $this->pobox);
        $this->addChild($root, 'ext', $this->ext);
        $this->addChild($root, 'street', $this->street);
        $this->addChild($root, 'locality', $this->locality);
        $this->addChild($root, 'region', $this->region);
        $this->addChild($root, 'code', $this->code);
        $this->addChild($root, 'country', $this->country);
    }

    private function addChild(&$n, $tag, $value, $ignoreEmpty = true)
    {
        if ($ignoreEmpty && $value === null) {
            return;
        }
        $n->addChild($tag, $value);
    }
}

class VCARDTelephoneInfo
{
    public $parameters = array(
        'type1' => null,
        'type2' => null);
    public $phoneNumber;

    function __construct($phone, $type1 = 'work', $type2 = 'voice')
    {
        $this->parameters['type1'] = $type1;
        $this->parameters['type2'] = $type2;
        $this->phoneNumber = $phone;
    }

    function toXML($tel)
    {
        $param = $tel->addChild('parameters');
        $param->addChild('type', $this->parameters['type1']);
        $param->addChild('type', $this->parameters['type2']);
        $tel->addChild('uri', $this->phoneNumber);
    }
}

class VCard
{
    const VCARD_21 = 'vcard21';
    const VCARD_30 = 'vcard30';
    const VCARD_40 = 'vcard40';
    const XCARD = 'xcard';
    public $SureName;
    public $GivenName;
    public $AdditionalName;
    public $PrefixName;
    public $SuffixName;
    public $FormattedName;
    public $Organization;
    public $Title;
    public $PhotoURI;
    public $Email;
    private $_telephone = array();
    /**
     *
     * Enter description here ...
     * @var VCARDAddressInfo
     */
    public $Address;

    public function addPhone(VCARDTelephoneInfo $phone)
    {
        $this->_telephone[] = $phone;
    }

    function __construct()
    {
        $this->Address = new VCARDAddressInfo();
    }

    public function generateXCard()
    {
        $tag = <<< EOT
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
  <vcard>
  </vcard>
</vcards>
EOT;
        $xml = new \SimpleXMLElement($tag);
        $root = $xml->vcard[0];
        $n = $root->AddChild('n');
        $this->addChild($n, 'surename', $this->SureName);
        $this->addChild($n, 'given', $this->GivenName);
        $this->addChild($n, 'additional', $this->AdditionalName);
        $this->addChild($n, 'prefix', $this->PrefixName);
        $this->addChild($n, 'suffix', $this->SuffixName);
        $this->addChild($root, 'fn', $this->FormattedName);
        $this->addChild($root, 'title', $this->Title);
        $this->addChild($root->addChild('photo'), 'uri', $this->PhotoURI);
        foreach ($this->_telephone as $tel) {
            $t = $root->addChild('tel');
            $tel->toXML($t);
        }
        $t = $root->addChild('addr');
        $this->Address->toXML($t);
        return $xml->saveXML();
    }

    private function addChild(&$n, $tag, $value, $ignoreEmpty = true)
    {
        if ($ignoreEmpty && $value === null) {
            return;
        }
        $n->addChild($tag, $value);
    }

    function generateVCard4()
    {
        $retval = array();
        $retval[] = 'BEGIN:VCARD';
        $retval[] = 'VERSION:4.0';
        $retval[] = 'N:' . $this->GivenName;
        $this->PrefixName;
        $this->SuffixName;
        $retval[] = 'FN:' . $this->SureName;
        $retval[] = 'ORG:' . $this->Organization;
        $retval[] = 'TITLE:' . $this->Title;
        $retval[] = 'PHOTO:' . $this->PhotoURI;
        foreach ($this->_telephone as $tel) {
            $retval[] = $tel->asVCARD();
        }
        //$retvTEL;TYPE="work,voice";VALUE=uri:tel:+1-111-555-1212
        //TEL;TYPE="home,voice";VALUE=uri:tel:+1-404-555-1212
        $retval[] = 'ADDR;TYPE=' . $this->Address->addrType . ';LABEL="' . str_replace("\n", '\n', strip_tags($this->Address->address)) . '":;;' . $this->Address->street . ';' . $this->Address->locality . ';' . $this->Address->region . ';' . $this->Address->code . ';' . $this->Address->country;
        $retval[] = 'EMAIL:' . $this->Email;
        $retval[] = 'END:VCARD';
        return implode($retval, "\n");
        //$retval[] = 'REV:'.date()
        /*ADR;TYPE=work;LABEL="42 Plantation St.\nBaytown, LA 30314\nUnited States of America"
        :;;42 Plantation St.;Baytown;LA;30314;United States of America*;
        EMAIL:forrestgump@example.com
        REV:20080424T195243Z
        END:VCARD
        ppd($this);*/
    }

    function generate($version = VCARD::XCARD)
    {
        switch ($version) {
            case self::XCARD:
                return $this->generatexcard();
                break;
            case self::VCARD_40:
                return $this->generateVCard4();
            default:
                ;
                break;
        }
    }
}
