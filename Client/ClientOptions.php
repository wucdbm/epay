<?php

namespace Wucdbm\Component\Epay\Client;

use Symfony\Component\OptionsResolver\OptionsResolver;

class ClientOptions {

    const IS_TEST = 'is_test';
    const SUBMIT_BUTTON = 'submit_button';
    const SUBMIT_URL = 'submit_url';
    const EASY_PAY_URL = 'easy_pay_url';
    const MERCHANT_ID = 'merchant_id';
    const MERCHANT_SECRET = 'merchant_secret';
    const HMAC_ALGO = 'hmac_algo';

    protected $options;

    public function get($option) {
        return $this->options[$option];
    }

    public function getIsTest() {
        return $this->get(self::IS_TEST);
    }

    public function getSubmitButton() {
        return $this->get(self::SUBMIT_BUTTON);
    }

    public function getSubmitUrl() {
        return $this->get(self::SUBMIT_URL);
    }

    public function getEasyPayUrl() {
        return $this->get(self::EASY_PAY_URL);
    }

    public function getMerchantId() {
        return $this->get(self::MERCHANT_ID);
    }

    public function getMerchantSecret() {
        return $this->get(self::MERCHANT_SECRET);
    }

    public function getHmacAlgo() {
        return $this->get(self::HMAC_ALGO);
    }

    public function __construct(array $options) {
        $resolver = new OptionsResolver();

        $resolver->setDefaults([
            self::SUBMIT_BUTTON => '<input type="submit" value="To Epay.bg" class="btn btn-success btn-block"/>',
            self::IS_TEST       => true,
            self::HMAC_ALGO     => 'sha1'
        ]);

        $resolver->setRequired([
            self::MERCHANT_ID,
            self::MERCHANT_SECRET
        ]);

        $resolver->setDefined([
            self::SUBMIT_URL,
            self::EASY_PAY_URL
        ]);

        $this->options = $resolver->resolve($options);

        if (!isset($this->options[self::SUBMIT_URL])) {
            if ($this->getIsTest()) {
                $this->options[self::SUBMIT_URL] = EpayClient::SUBMIT_URL_EPAY_TEST;
            } else {
                $this->options[self::SUBMIT_URL] = EpayClient::SUBMIT_URL_EPAY_PROD;
            }
        }

        if (!isset($this->options[self::EASY_PAY_URL])) {
            if ($this->getIsTest()) {
                $this->options[self::EASY_PAY_URL] = EpayClient::SUBMIT_URL_EASY_PAY_TEST;
            } else {
                $this->options[self::EASY_PAY_URL] = EpayClient::SUBMIT_URL_EASY_PAY_PROD;
            }
        }
    }

}