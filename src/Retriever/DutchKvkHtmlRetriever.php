<?php
namespace Werkspot\Component\ChamberOfCommerce\Retriever;

use Guzzle\Http\ClientInterface;
use Guzzle\Http\Exception\CurlException;
use Guzzle\Http\Message\Response;
use Werkspot\Component\ChamberOfCommerce\Exception\InvalidChamberOfCommerceResponseException;
use Werkspot\Component\ChamberOfCommerce\Exception\ParseExceptionInterface;
use Werkspot\Component\ChamberOfCommerce\Exception\ServiceUnavailableException;
use Werkspot\Component\ChamberOfCommerce\Exception\UnexpectedHttpStatusCodeException;
use Werkspot\Component\ChamberOfCommerce\Parser\ChamberOfCommerceParser;
use Werkspot\Component\ChamberOfCommerce\Parser\ChamberOfCommerceResponseParser;

class DutchKvkHtmlRetriever implements ChamberOfCommerceRetriever
{
    /**
     * @var \Guzzle\Http\ClientInterface
     */
    private $client;

    /**
     * @var string
     */
    private $url;

    /**
     * @var ChamberOfCommerceResponseParser
     */
    private $parser;


    /**
     * @param ClientInterface $client
     * @param string $url
     * @param ChamberOfCommerceResponseParser $parser
     */
    public function __construct(ClientInterface $client, $url, ChamberOfCommerceResponseParser $parser=null)
    {
        $this->client = $client;
        $this->url = $url;
        $this->parser = $parser ?: new ChamberOfCommerceParser();
    }

    /**
     * {@inheritdoc}
     */
    public function find($chamberOfCommerceNumber)
    {
        $response = $this->getResponseOrThrowException($chamberOfCommerceNumber);
        $this->validateResponseStatusCodeOrThrowException($chamberOfCommerceNumber, $response);

        try {
            return $this->parser->parse($chamberOfCommerceNumber, $response);
        } catch (ParseExceptionInterface $e) {
            throw new InvalidChamberOfCommerceResponseException($chamberOfCommerceNumber, $response->getBody(true), 0, $e);
        }
    }

    /**
     * @param string $chamberOfCommerceNumber
     * @return Response
     * @throws ServiceUnavailableException
     */
    protected function getResponseOrThrowException($chamberOfCommerceNumber)
    {
        try {
            return $this->client->get($this->url . $chamberOfCommerceNumber)->send();
        } catch (CurlException $e) {
            $host = parse_url($this->url, PHP_URL_HOST);
            throw new ServiceUnavailableException($chamberOfCommerceNumber, $host);
        }
    }

    /**
     * @param $chamberOfCommerceNumber
     * @param Response $response
     * @throws UnexpectedHttpStatusCodeException
     */
    public function validateResponseStatusCodeOrThrowException($chamberOfCommerceNumber, Response $response)
    {
        if ($response->getStatusCode() !== 200) {
            throw new UnexpectedHttpStatusCodeException(
                $chamberOfCommerceNumber,
                200,
                $response->getStatusCode(),
                $response->getEffectiveUrl()
            );
        }
    }
}