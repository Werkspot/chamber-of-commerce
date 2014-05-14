<?php

namespace Werkspot\Component\ChamberOfCommerce\Model;

class ChamberOfCommerceRecord
{
    /** @var string|int */
    private $number;

    /** @var string */
    private $countryCode;

    /** @var string */
    private $name;

    /** @var string */
    private $streetName;

    /** @var string|int */
    private $houseNumber;

    /** @var string|int */
    private $houseNumberAddition;

    /** @var string */
    private $zipCode;

    /** @var string */
    private $city;

    /** @var string */
    private $internetAddress;

    /**
     * @param string|int $number
     * @param string $name
     * @param string $countryCode
     * @param string $zipCode
     * @param string $city
     * @param string $streetName
     * @param string|int $houseNumber
     * @param string|int $houseNumberAddition
     * @param string $internetAddress
     */
    public function __construct($number, $name, $countryCode, $zipCode, $city, $streetName, $houseNumber, $houseNumberAddition, $internetAddress)
    {
        $this->number = $number;
        $this->name = $name;
        $this->countryCode = $countryCode;
        $this->zipCode = $zipCode;
        $this->city = $city;
        $this->streetName = $streetName;
        $this->houseNumber = $houseNumber;
        $this->houseNumberAddition = $houseNumberAddition;
        $this->internetAddress = $internetAddress;
    }

    /**
     * @return string|int
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getStreetName()
    {
        return $this->streetName;
    }

    /**
     * @return int|string
     */
    public function getHouseNumber()
    {
        return $this->houseNumber;
    }

    /**
     * @return string|int
     */
    public function getHouseNumberAddition()
    {
        return $this->houseNumberAddition;
    }

    /**
     * @return string
     */
    public function getInternetAddress()
    {
        return $this->internetAddress;
    }

    /**
     * @return string
     */
    public function getZipCode()
    {
        return $this->zipCode;
    }

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @return string
     */
    public function getCountryCode()
    {
        return $this->countryCode;
    }
}