<?php

namespace CodeDredd\Soap\XML;

class SoapXml extends \Phpro\SoapClient\Xml\SoapXml
{
    /**
     * @return string
     */
    public function getFaultMessage(): string
    {
        $list = $this->getXmlDocument()->getElementsByTagName('faultstring');

        return $list->length ? $list->item(0)->firstChild->nodeValue : 'No Fault Message found';
    }
}