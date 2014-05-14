<?php

namespace Werkspot\Component\ChamberOfCommerce\Tests\Model;

use PHPUnit_Framework_TestCase;
use Werkspot\Component\ChamberOfCommerce\Model\ChamberOfCommerceRecord;

class ChamberOfCommerceRecordTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getChamberOfCommerceData
     *
     * @param string|int $number
     * @param string $name
     * @param $countryCode
     * @param string $zipCode
     * @param string $city
     * @param string $streetName
     * @param string|int $houseNumber
     * @param string|int $houseNumberAddition
     * @param string $internetAddress
     */
    public function testChamberOfCommerceRecord($number, $name, $countryCode, $zipCode, $city, $streetName, $houseNumber, $houseNumberAddition, $internetAddress)
    {
        $record = new ChamberOfCommerceRecord($number, $name, $countryCode, $zipCode, $city, $streetName, $houseNumber, $houseNumberAddition, $internetAddress);

        $this->assertSame($number, $record->getNumber());
        $this->assertSame($name, $record->getName());
        $this->assertSame($countryCode, $record->getCountryCode());
        $this->assertSame($zipCode, $record->getZipCode());
        $this->assertSame($city, $record->getCity());
        $this->assertSame($streetName, $record->getStreetName());
        $this->assertSame($houseNumber, $record->getHouseNumber());
        $this->assertSame($houseNumberAddition, $record->getHouseNumberAddition());
        $this->assertSame($internetAddress, $record->getInternetAddress());
    }

    /**
     * @return array
     */
    public function getChamberOfCommerceData()
    {
        return array(
            array('00000001', 'Ping', 'nl', '1000AA', 'Amsterdam', 'de Dam', 1, null, 'www.amsterdam.nl'),
            array(0, 1, 2, 3, 4, 5, 6, 7, 8),
            array(null, null, null, null, null, null, null, null, null),
        );
    }
}