<?php
namespace Werkspot\Component\ChamberOfCommerce\Parser;

use Guzzle\Http\Message\Response;
use Werkspot\Component\ChamberOfCommerce\Exception\ParseExceptionInterface;
use Werkspot\Component\ChamberOfCommerce\Model\ChamberOfCommerceRecord;

interface ChamberOfCommerceResponseParser
{
    /**
     * @param string $chamberOfCommerceNumber
     * @param Response $response
     * @return ChamberOfCommerceRecord
     * @throws ParseExceptionInterface
     */
    public function parse($chamberOfCommerceNumber, Response $response);
}
