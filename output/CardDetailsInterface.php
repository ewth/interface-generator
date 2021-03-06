<?php

// Interface generated by https://github.com/ewth/interface-generator
// Do not use without reviewing first!

interface CardDetailsInterface
{

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName(string $name): self;

    /**
     * @return string
     */
    public function getNumber(): string;

    /**
     * @param string $number
     *
     * @return $this
     */
    public function setNumber(string $number): self;

    /**
     * @return string
     */
    public function getExpiryMonth(): string;

    /**
     * @param string $expiryMonth
     *
     * @return $this
     */
    public function setExpiryMonth(string $expiryMonth): self;

    /**
     * @return string
     */
    public function getExpiryYear(): string;

    /**
     * @param string $expiryYear
     *
     * @return $this
     */
    public function setExpiryYear(string $expiryYear): self;

    /**
     * @return string
     */
    public function getStartMonth(): string;

    /**
     * @param string $startMonth
     *
     * @return $this
     */
    public function setStartMonth(string $startMonth): self;

    /**
     * @return string
     */
    public function getStartYear(): string;

    /**
     * @param string $startYear
     *
     * @return $this
     */
    public function setStartYear(string $startYear): self;

    /**
     * @return string
     */
    public function getIssueNumber(): string;

    /**
     * @param string $issueNumber
     *
     * @return $this
     */
    public function setIssueNumber(string $issueNumber): self;

    /**
     * @return string
     */
    public function getCvn(): string;

    /**
     * @param string $cvn
     *
     * @return $this
     */
    public function setCvn(string $cvn): self;

}
