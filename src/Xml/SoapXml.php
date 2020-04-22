<?php

namespace CodeDredd\Soap\Xml;

class SoapXml extends \Phpro\SoapClient\Xml\SoapXml
{
    /**
     * Get the error message from a SoapFault instance
     *
     * @return string
     */
    public function getFaultMessage(): string
    {
        $list = $this->getXmlDocument()->getElementsByTagName('faultstring');

        return $list->length ? $list->item(0)->firstChild->nodeValue : 'No Fault Message found';
    }
}
