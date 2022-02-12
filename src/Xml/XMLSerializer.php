<?php

namespace CodeDredd\Soap\Xml;

use DOMDocument;
use DOMNode;
use Illuminate\Support\Str;
use SimpleXMLElement;

/**
 * Class XMLSerializer.
 */
class XMLSerializer
{
    /**
     * Recursive function to turn a DOMDocument element to an array.
     *
     * @param  DOMDocument|DomNode  $node  the document (might also be a DOMElement/DOMNode?)
     * @return array
     */
    public static function domNodeToArray($node)
    {
        $output = [];
        switch ($node->nodeType) {
            case XML_CDATA_SECTION_NODE:
            case XML_TEXT_NODE:
                $output = trim($node->textContent);

                break;
            case XML_ELEMENT_NODE:
                for ($i = 0, $m = $node->childNodes->length; $i < $m; $i++) {
                    $child = $node->childNodes->item($i);
                    $v = self::domNodeToArray($child);
                    if (isset($child->tagName)) {
                        $t = Str::after($child->tagName, ':');
                        if (! isset($output[$t])) {
                            $output[$t] = [];
                        }
                        $output[$t][] = $v;
                    } elseif (($v || $v === '0')) {
                        $output = is_array($v) ? json_encode($v) : $v;
                    }
                }
                if ($node->attributes->length && ! is_array($output)) { // Has attributes but isn't an array
                    $output = ['@content' => $output]; // Change output into an array.
                }
                if (is_array($output)) {
                    if ($node->attributes->length) {
                        $a = [];
                        foreach ($node->attributes as $attrName => $attrNode) {
                            $a[$attrName] = (string) $attrNode->value;
                        }
                        $output['@attributes'] = $a;
                    }
                    foreach ($output as $t => $v) {
                        if (is_array($v) && count($v) == 1 && $t != '@attributes') {
                            $output[$t] = $v[0];
                        }
                    }
                }

                break;
        }

        return $output;
    }

    /**
     * Return a valid SOAP Xml.
     *
     * @param  array  $array
     * @return mixed
     */
    public static function arrayToSoapXml(array $array)
    {
        $array = [
            'SOAP-ENV:Body' => $array,
        ];
        $xml = new SimpleXMLElement('<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"/>');
        self::addArrayToXml($array, $xml);

        return $xml->asXML();
    }

    public static function addArrayToXml(array $array, &$xml)
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                if (is_int($key)) {
                    $key = 'node';
                }
                $label = $xml->addChild($key);
                self::addArrayToXml($value, $label);
            } else {
                $xml->addChild($key, $value);
            }
        }
    }
}
