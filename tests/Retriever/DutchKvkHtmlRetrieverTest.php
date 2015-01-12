<?php

namespace Werkspot\Component\ChamberOfCommerce\Tests\Retriever;

use PHPUnit_Framework_TestCase;
use Guzzle\Http\Client;
use Guzzle\Plugin\Mock\MockPlugin;
use Guzzle\Http\Message\Response;
use Guzzle\Http\Exception\CurlException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Werkspot\Component\ChamberOfCommerce\Retriever\DutchKvkHtmlRetriever;
use Werkspot\Component\ChamberOfCommerce\Model\ChamberOfCommerceRecord;

class DutchKvkHtmlRetrieverTest extends PHPUnit_Framework_TestCase
{
    const URL = 'https://server.db.kvk.nl/TST-BIN/FU/TSWS001@?BUTT=';

    /**
     * @dataProvider getValidChamberOfCommerceNumbers
     *
     * @param string|int $number
     * @param string $name
     * @param string $zipCode
     * @param string $city
     * @param string $streetName
     * @param int $houseNumber
     * @param mixed $houseNumberAddition
     * @param string $internetAddress
     */
    public function testFindWithValidChamberOfCommerceNumber($number, $name, $zipCode, $city, $streetName, $houseNumber, $houseNumberAddition, $internetAddress)
    {
        $client = $this->getClientThatReturnsResponseChamberOfCommerceDataWithStatusCode($number);

        $retriever = new DutchKvkHtmlRetriever($client, self::URL);
        $givenChamberOfCommerce = $retriever->find($number);

        $expectedChamberOfCommerce = new ChamberOfCommerceRecord($number, $name, 'nl', $zipCode, $city, $streetName, $houseNumber, $houseNumberAddition, $internetAddress);

        $this->assertEquals($expectedChamberOfCommerce, $givenChamberOfCommerce);
    }

    /**
     * @return array
     */
    public function getValidChamberOfCommerceNumbers()
    {
        return array(
            array('18079951', 'Werkspot B.V.', '1017BS', 'Amsterdam', 'Herengracht', 469, null, 'www.werkspot.nl'),
            array('24304157', 'Qweb Internet Services', '3115JC', 'Schiedam', 'Piet Heinstraat', 7, null, 'www.servers.nl'),
            array('05079434', 'Ping-Ping Ommen B.V.', '7731DB', 'Ommen', 'Markt', '32', null, null),
            array('18080015', 'PARC polyester and rubber repair B.V.', '4251LA', 'Werkendam', 'Bruningsstraat', '21', '23', null),
            array('18080039', 'Stichting ARTV', '5021LL', 'Tilburg', 'Groenstraat', '139', '391', null),
            array('18080051', 'Tylo B.V.', '5038BC', 'Tilburg', 'Willem II-straat', '45', 'a', 'www.tylo.nl'),
            array('18079994', 'Orthopedische schoentechniek "de Biest" B.V.', '5084HT', 'Biest-Houtakker', 'Biestsestraat', '105', null, null),
        );
    }

    public function testFindWithValidChamberOfCommerceNumber_thatReturnsTheFirstEightCharactersOfChamberOfCommerceNumber()
    {
        $chamberOfCommerceNumber = '180800510000';
        $expectedChamberOfCommerceNumber = '18080051';

        $client = $this->getClientThatReturnsResponseChamberOfCommerceDataWithStatusCode($chamberOfCommerceNumber);

        $retriever = new DutchKvkHtmlRetriever($client, self::URL);
        $givenChamberOfCommerce = $retriever->find($chamberOfCommerceNumber);

        $this->assertSame($expectedChamberOfCommerceNumber, $givenChamberOfCommerce->getNumber());
    }

    /**
     * @expectedException \Werkspot\Component\ChamberOfCommerce\Exception\ServiceUnavailableException
     * @expectedExceptionMessage server.db.kvk.nl
     */
    public function testFindWhileServiceIsUnavailable()
    {
        $client = $this->getClientThatThrowsException(new CurlException());

        $number = 1;

        $retriever = new DutchKvkHtmlRetriever($client, self::URL);
        $retriever->find($number);
    }

    /**
     * @dataProvider getNonExistingChamberOfCommerceNumbers
     *
     * @expectedException \Werkspot\Component\ChamberOfCommerce\Exception\NotFoundException
     */
    public function testFindNonexistentChamberOfCommerceNumber($number)
    {
        $client = $this->getClientThatReturnsResponseChamberOfCommerceDataWithStatusCode($number);

        $retriever = new DutchKvkHtmlRetriever($client, self::URL);
        $retriever->find($number);
    }

    /**
     * @return array
     */
    public function getNonExistingChamberOfCommerceNumbers()
    {
        return array(
            array('07079435'),
            array('08079951'),
            array('offline'),
        );
    }

    /**
     * @dataProvider getChamberOfCommerceNumbersWithInvalidStatus
     */
    public function testChamberOfCommerceNumberWithInvalidStatus($number, $status)
    {
        $expectedException = '\Werkspot\Component\ChamberOfCommerce\Exception\InvalidChamberOfCommerceStatusException';
        $expectedExceptionMessage = sprintf('number [%s] has invalid status [%s]', $number, $status);
        $this->setExpectedException($expectedException, $expectedExceptionMessage);

        $client = $this->getClientThatReturnsResponseChamberOfCommerceDataWithStatusCode($number);

        $retriever = new DutchKvkHtmlRetriever($client, self::URL);
        $retriever->find($number);
    }

    /**
     * @return array
     */
    public function getChamberOfCommerceNumbersWithInvalidStatus()
    {
        return array(
            array('05079420', 'Uitgeschreven uit het Handelsregister'),
            array('05079435', 'Uitgeschreven uit het Handelsregister'),
            array('18079980', 'Faillissement'),
        );
    }

    /**
     * @dataProvider getInvalidStatusCodes
     *
     * @param int $responseStatusCode
     */
    public function testFindThatReturnsInvalidResponseStatusCode($responseStatusCode)
    {
        $expectedException = '\Werkspot\Component\ChamberOfCommerce\Exception\UnexpectedHttpStatusCodeException';
        $expectedExceptionMessage = sprintf('Expected status code [%d] but got status code [%d]', 200, $responseStatusCode);
        $this->setExpectedException($expectedException, $expectedExceptionMessage);

        $number = '18079951';

        $client = $this->getClientThatReturnsResponseChamberOfCommerceDataWithStatusCode($number, $responseStatusCode);

        $retriever = new DutchKvkHtmlRetriever($client, self::URL);
        $retriever->find($number);
    }

    /**
     * @return array
     */
    public function getInvalidStatusCodes()
    {
        return array(
            array(201),
            array(202),
            array(205),
            array(301),
            array(302),
            array(304),
        );
    }

    /**
     * @dataProvider getChamberOfCommerceNumbersWithInvalidResponse
     *
     * @expectedException \Werkspot\Component\ChamberOfCommerce\Exception\InvalidChamberOfCommerceResponseException
     *
     * @param string|int $number
     */
    public function testFindWithInvalidResponse($number)
    {
        $client = $this->getClientThatReturnsResponseChamberOfCommerceDataWithStatusCode($number);

        $retriever = new DutchKvkHtmlRetriever($client, self::URL);
        $retriever->find($number);
    }

    /**
     * @return array
     */
    public function getChamberOfCommerceNumbersWithInvalidResponse()
    {
        return array(
            array('too-few-table-cells'),
            array('too-many-table-cells'),
        );
    }

    /**
     * @param $number
     * @param int $statusCode
     * @return Client
     */
    private function getClientThatReturnsResponseChamberOfCommerceDataWithStatusCode($number, $statusCode = 200)
    {
        $response = new Response($statusCode, array(), $this->getResponseDataForChamberOfCommerceNumber($number));
        $plugin = new MockPlugin();
        $plugin->addResponse($response);

        return $this->getClientAndAddSubscriber($plugin);
    }

    /**
     * @param CurlException $e
     * @return Client
     */
    private function getClientThatThrowsException(CurlException $e)
    {
        $plugin = new MockPlugin();
        $plugin->addException($e);

        return $this->getClientAndAddSubscriber($plugin);
    }

    /**
     * @param EventSubscriberInterface $s
     * @return Client
     */
    private function getClientAndAddSubscriber(EventSubscriberInterface $s)
    {
        $c = new Client();
        $c->addSubscriber($s);

        return $c;
    }

    /**
     * @param string $chamberOfCommerceNumber
     * @return string
     */
    private function getResponseDataForChamberOfCommerceNumber($chamberOfCommerceNumber)
    {
        return file_get_contents(dirname(__FILE__) . '/../data/dutch-kvk-html/' . $chamberOfCommerceNumber . '.html');
    }
}