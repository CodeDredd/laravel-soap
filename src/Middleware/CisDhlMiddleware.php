<?php

namespace CodeDredd\Soap\Middleware;

use Http\Client\Common\Plugin;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Soap\Psr18Transport\Xml\XmlMessageManipulator;
use Soap\Xml\Builder\SoapHeader;
use Soap\Xml\Builder\SoapHeaders;
use Soap\Xml\Manipulator\PrependSoapHeaders;
use function VeeWee\Xml\Dom\Builder\children;
use function VeeWee\Xml\Dom\Builder\namespaced_element;
use function VeeWee\Xml\Dom\Builder\value;
use VeeWee\Xml\Dom\Document;

class CisDhlMiddleware implements Plugin
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

    public function handleRequest(RequestInterface $request, callable $next, callable $first): Promise
    {
        return $next(
            (new XmlMessageManipulator)(
                $request,
                function (Document $document) {
                    $builder = new SoapHeaders(
                        new SoapHeader(
                            self::CIS_NS,
                            'cis:Authentification',
                            children(
                                namespaced_element(self::CIS_NS, 'user', value($this->user)),
                                namespaced_element(self::CIS_NS, 'signature', value($this->signature))
                            )
                        )
                    );

                    $headers = $document->build($builder);

                    return $document->manipulate(new PrependSoapHeaders(...$headers));
                }
            )
        );
    }

    /**
     * @param  ResponseInterface  $response
     * @return ResponseInterface
     */
    public function afterResponse(ResponseInterface $response): ResponseInterface
    {
        return $response;
    }
}
