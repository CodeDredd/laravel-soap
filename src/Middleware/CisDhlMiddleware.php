<?php

namespace CodeDredd\Soap\Middleware;

use Http\Promise\Promise;
use Phpro\SoapClient\Middleware\Middleware;
use Phpro\SoapClient\Xml\SoapXml;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class CisDhlMiddleware extends Middleware
{
    /**
     * @var string
     */
    const CIS_NS = 'http://dhl.de/webservice/cisbase';

    /**
     * @var string
     */
    private $user;

    /**
     * @var string
     */
    private $signature;

    public function __construct(string $user, string $signature)
    {
        $this->user = $user;
        $this->signature = $signature;
    }

    public function getName(): string
    {
        return 'cis_dhl_middleware';
    }

    /**
     * @param callable         $handler
     * @param RequestInterface $request
     *
     * @return Promise
     */
    public function beforeRequest(callable $handler, RequestInterface $request): Promise
    {
        $xml = SoapXml::fromStream($request->getBody());
        $xml->registerNamespace('cis', 'http://dhl.de/webservice/cisbase');
        $envelope = $xml->xpath('/soap:Envelope')->item(0);

        $domDoc = $xml->getXmlDocument();
        $domDoc->documentElement->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:cis', self::CIS_NS);

        $header = $domDoc->createElementNS('http://schemas.xmlsoap.org/soap/envelope/', 'SOAP-ENV:Header');
        $cisAuth = $domDoc->createElementNS(self::CIS_NS, 'cis:Authentification');

        $envelope->insertBefore($header, $envelope->firstChild);

        if (! empty($this->user) && ! empty($this->signature)) {
            $cisUser = $domDoc->createElementNS(self::CIS_NS, 'cis:user', $this->user);
            $cisSig = $domDoc->createElementNS(self::CIS_NS, 'cis:signature', $this->signature);
            $cisAuth->appendChild($cisUser);
            $cisAuth->appendChild($cisSig);
        }
        $header->appendChild($cisAuth);

        return $handler($request->withBody($xml->toStream()));
    }

    /**
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    public function afterResponse(ResponseInterface $response): ResponseInterface
    {
        return $response;
    }
}
