<?php

namespace Wucdbm\Component\Epay\Client;

class ClientOptions {

    const IS_TEST = 'is_test';
    const SUBMIT_BUTTON = 'submit_button';
    const SUBMIT_URL = 'submit_url';
    const EASY_PAY_URL = 'easy_pay_url';
    const MERCHANT_ID = 'merchant_id';
    const MERCHANT_SECRET = 'merchant_secret';
    const HMAC_ALGO = 'hmac_algo';

    /** @var bool */
    protected $debug = true;

    /** @var string */
    protected $submitButton = '<input type="submit" value="To Epay.bg" class="btn btn-success btn-block"/>';

    /** @var  string */
    protected $submitUrl;

    /** @var  string */
    protected $easyPayUrl;

    /** @var  string */
    protected $merchantId;

    /** @var  string */
    protected $merchantSecret;

    /** @var string */
    protected $hmacAlgo = 'sha1';

    public function configureUrls() {
        if ($this->isDebug()) {
            $this->submitUrl = EpayClient::SUBMIT_URL_EPAY_TEST;
            $this->easyPayUrl = EpayClient::SUBMIT_URL_EASY_PAY_TEST;
        } else {
            $this->submitUrl = EpayClient::SUBMIT_URL_EPAY_PROD;
            $this->easyPayUrl = EpayClient::SUBMIT_URL_EASY_PAY_PROD;
        }
    }

    public function __construct($merchantId, $merchantSecret, $isDebug = true) {
        $this->merchantId = $merchantId;
        $this->merchantSecret = $merchantSecret;
        $this->debug = $isDebug;
        $this->configureUrls();
    }

    /**
     * @return boolean
     */
    public function isDebug() {
        return $this->debug;
    }

    /**
     * @param boolean $debug
     */
    public function setDebug($debug) {
        $this->debug = $debug;
    }

    /**
     * @return string
     */
    public function getSubmitButton() {
        return $this->submitButton;
    }

    /**
     * @param string $submitButton
     */
    public function setSubmitButton($submitButton) {
        $this->submitButton = $submitButton;
    }

    /**
     * @return string
     */
    public function getSubmitUrl() {
        return $this->submitUrl;
    }

    /**
     * @param string $submitUrl
     */
    public function setSubmitUrl($submitUrl) {
        $this->submitUrl = $submitUrl;
    }

    /**
     * @return string
     */
    public function getEasyPayUrl() {
        return $this->easyPayUrl;
    }

    /**
     * @param string $easyPayUrl
     */
    public function setEasyPayUrl($easyPayUrl) {
        $this->easyPayUrl = $easyPayUrl;
    }

    /**
     * @return string
     */
    public function getMerchantId() {
        return $this->merchantId;
    }

    /**
     * @param string $merchantId
     */
    public function setMerchantId($merchantId) {
        $this->merchantId = $merchantId;
    }

    /**
     * @return string
     */
    public function getMerchantSecret() {
        return $this->merchantSecret;
    }

    /**
     * @param string $merchantSecret
     */
    public function setMerchantSecret($merchantSecret) {
        $this->merchantSecret = $merchantSecret;
    }

    /**
     * @return string
     */
    public function getHmacAlgo() {
        return $this->hmacAlgo;
    }

    /**
     * @param string $hmacAlgo
     */
    public function setHmacAlgo($hmacAlgo) {
        $this->hmacAlgo = $hmacAlgo;
    }

}