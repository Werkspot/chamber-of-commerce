<?php

namespace Werkspot\Component\ChamberOfCommerce\Retriever;

use DOMNode;
use Guzzle\Http\ClientInterface;
use Guzzle\Http\Exception\CurlException;
use Guzzle\Http\Message\Response;
use Symfony\Component\DomCrawler\Crawler;
use Werkspot\Component\ChamberOfCommerce\Exception\InvalidChamberOfCommerceResponseException;
use Werkspot\Component\ChamberOfCommerce\Exception\ParseExceptionInterface;
use Werkspot\Component\ChamberOfCommerce\Exception\InvalidChamberOfCommerceStatusException;
use Werkspot\Component\ChamberOfCommerce\Exception\InvalidTableCellCount;
use Werkspot\Component\ChamberOfCommerce\Exception\NotFoundException;
use Werkspot\Component\ChamberOfCommerce\Exception\ServiceUnavailableException;
use Werkspot\Component\ChamberOfCommerce\Exception\UnexpectedHttpStatusCodeException;
use Werkspot\Component\ChamberOfCommerce\Model\ChamberOfCommerceRecord;

class DutchKvkHtmlRetriever implements ChamberOfCommerceRetriever
{
    /** @var \Guzzle\Http\ClientInterface */
    private $client;

    /** @var string */
    private $url;

    /**
     * @param ClientInterface $client
     * @param string $url
     */
    public function __construct(ClientInterface $client, $url)
    {
        $this->client = $client;
        $this->url = $url;
    }

    /**
     * {@inheritdoc}
     */
    public function find($chamberOfCommerceNumber)
    {
        $response = $this->getResponseOrThrowException($chamberOfCommerceNumber);
        $this->validateResponseStatusCodeOrThrowException($chamberOfCommerceNumber, $response);

        try {
            return $this->parseResponse($chamberOfCommerceNumber, $response);
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

    /**
     * @param $chamberOfCommerceNumber
     * @param Response $response
     * @return ChamberOfCommerceRecord
     * @throws InvalidChamberOfCommerceStatusException
     * @throws InvalidTableCellCount
     */
    protected function parseResponse($chamberOfCommerceNumber, Response $response)
    {
        $fetchedChamberOfCommerceNumber = null;
        $name = $internetAddress = $streetName = $houseNumber = $houseNumberAddition = $zipCode = $city = null;

        foreach ($this->getTableRows($response) as $row) {
            $cells = $this->getTwoTableCellsOrThrowException($row);
            $propertyName = $this->getPropertyName($cells);
            $propertyValue = $this->getPropertyValue($cells);

            switch ($propertyName) {
                case 'naam':
                    $name = $propertyValue;
                    break;
                case 'kvk-nummer':
                    $fetchedChamberOfCommerceNumber = $propertyValue;
                    break;
                case 'internetadres':
                    $internetAddress = $propertyValue;
                    break;
                case 'vestigingsadres':
                    list($streetName, $houseNumber, $houseNumberAddition) = explode("\n", $propertyValue);
                    $streetName = $this->trim($streetName);
                    $houseNumber = $this->trim($houseNumber);
                    $houseNumberAddition = $this->normalizeHouseNumberAddition($houseNumberAddition);
                    break;
                case 'vestigingsplaats':
                    list($zipCode, $city) = explode("\n", $propertyValue);
                    $zipCode = $this->trim($zipCode);
                    $city = $this->trim($city);
                    break;
                case 'status':
                    throw new InvalidChamberOfCommerceStatusException($chamberOfCommerceNumber, $this->trim($propertyValue));
            }
        }

        $this->validateChamberOfCommerceNumberOrThrowException($chamberOfCommerceNumber, $fetchedChamberOfCommerceNumber);

        // TODO: Validate other fields? name, city, zipCode, street, houseNumber if present?

        return new ChamberOfCommerceRecord($fetchedChamberOfCommerceNumber, $name, 'nl', $zipCode, $city, $streetName, $houseNumber, $houseNumberAddition, $internetAddress);
    }

    /**
     * @param Response $response
     * @return Crawler
     */
    protected function getTableRows(Response $response)
    {
        $crawler = new Crawler($response->getBody(true));
        return  $crawler->filter('table:nth-child(2) tr');
    }

    /**
     * @param DOMNode $row
     * @return Crawler
     * @throws InvalidTableCellCount
     */
    protected function getTwoTableCellsOrThrowException(DOMNode $row)
    {
        $crawledRow = new Crawler($row);
        $cells = $crawledRow->filter('td');
        $cellCount = count($cells);

        if ($cellCount !== 2) {
            throw new InvalidTableCellCount(2, $cellCount);
        }

        return $cells;
    }

    /**
     * @param string $str
     * @return string
     */
    protected function trim($str)
    {
        return trim(trim($str, chr(0xC2).chr(0xA0)));
    }

    /**
     * @param string|int $number
     * @return null|string
     */
    protected function normalizeHouseNumberAddition($number)
    {
        $matches = array();
        if (!preg_match('/(\w+.*)$/', $number, $matches) === 1) {
            return null;
        }

        $addition = $this->trim($matches[1]);

        if (is_numeric($addition)) {
            return abs($addition);
        }

        return $addition;
    }

    /**
     * @param Crawler $cells
     * @return string
     */
    protected function getPropertyName(Crawler $cells)
    {
        $name = $cells->getNode(0)->nodeValue;
        return $this->normalizePropertyName($name);
    }

    /**
     * @param string $name
     * @return string
     */
    protected function normalizePropertyName($name)
    {
        return strtolower(str_replace(':', '', $name));
    }

    /**
     * @param Crawler $cells
     * @return string
     */
    protected function getPropertyValue(Crawler $cells)
    {
        return $cells->getNode(1)->nodeValue;
    }

    /**
     * @param string $requestedChamberOfCommerceNumber
     * @param string $fetchedChamberOfCommerceNumber
     * @throws NotFoundException
     */
    protected function validateChamberOfCommerceNumberOrThrowException($requestedChamberOfCommerceNumber, $fetchedChamberOfCommerceNumber)
    {
        if (empty($fetchedChamberOfCommerceNumber) || $requestedChamberOfCommerceNumber != $fetchedChamberOfCommerceNumber) {
            throw new NotFoundException($requestedChamberOfCommerceNumber);
        }
    }
}