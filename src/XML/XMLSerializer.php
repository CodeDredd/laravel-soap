<?php

namespace CodeDredd\Soap\XML;

use \DOMDocument;
use \DOMNode;
use Illuminate\Support\Str;

class XMLSerializer {

    /**
     * Recursive function to turn a DOMDocument element to an array
     *
     * @param DOMDocument|DomNode $root the document (might also be a DOMElement/DOMNode?)
     * @param bool $normalize
     *
     * @return array
     */
    public static function dom2Array($root, $normalize = false) {
        $array = array();

        //list attributes
        if($root->hasAttributes()) {
            foreach($root->attributes as $attribute) {
                $array['_attributes'][$attribute->name] = $attribute->value;
            }
        }

        //handle classic node
        if($root->nodeType == XML_ELEMENT_NODE) {

            if (!$normalize) {
                $array['_type'] = $root->nodeName;
            }
            if($root->hasChildNodes()) {
                $children = $root->childNodes;
                for($i = 0; $i < $children->length; $i++) {
                    $child = self::dom2Array( $children->item($i), $normalize);

                    //don't keep textnode with only spaces and newline
                    if(!empty($child)) {
                        if (!$normalize) {
                            $array['_children'][] = $child;
                        } else {
                            $array[Str::afterLast($root->nodeName, ':')] = $child;
                        }
                    }
                }
            }

            //handle text node
        } elseif($root->nodeType == XML_TEXT_NODE || $root->nodeType == XML_CDATA_SECTION_NODE) {
            $value = $root->nodeValue;
            if(!empty($value)) {
                if (!$normalize) {
                    $array['_type']    = '_text';
                    $array['_content'] = $value;
                } else {
                    $array = $value;
                }
            }
        }

        return $array;
    }

    /**
     * Recursive function to turn an array to a DOMDocument
     * @param array       $array the array
     * @param DOMDocument $doc   only used by recursion
     */
    public static function array2Dom($array, $doc = null) {
        if($doc == null) {
            $doc = new DOMDocument();
            $doc->formatOutput = true;
            $currentNode = $doc;
        } else {
            if($array['_type'] == '_text')
                $currentNode = $doc->createTextNode($array['_content']);
            else
                $currentNode = $doc->createElement($array['_type']);
        }

        if($array['_type'] != '_text') {
            if(isset($array['_attributes'])) {
                foreach ($array['_attributes'] as $name => $value) {
                    $currentNode->setAttribute($name, $value);
                }
            }

            if(isset($array['_children'])) {
                foreach($array['_children'] as $child) {
                    $childNode = self::array2Dom($child, $doc);
                    $currentNode->appendChild($childNode);
                }
            }
        }

        return $currentNode;
    }

	public static function arrayToXml($array, &$xml){
        foreach ($array as $key => $value) {
            if(is_array($value)){
                if(is_int($key)){
                    $key = "node";
                }
                $label = $xml->addChild($key);
                self::arrayToXml($value, $label);
            }
            else {
                $xml->addChild($key, $value);
            }
        }
    }
}
