<?php

namespace Wucdbm\Component\Epay\Response;

class ReceiveResponse {

    /**
     * @var PaymentResponse[]
     */
    protected $paymentResponces;

    public function toString() {
        $responses = [];
        /** @var PaymentResponse $response */
        foreach ($this->paymentResponces as $response) {
            $responses[] = $response->toString();
        }

        return implode("\n", $responses);
    }

    /**
     * ReceiveResponse constructor.
     * @param PaymentResponse[] $paymentResponces
     */
    public function __construct(array $paymentResponces) {
        $this->paymentResponces = $paymentResponces;
    }

    /**
     * @return PaymentResponse[]
     */
    public function getPaymentResponces() {
        return $this->paymentResponces;
    }

    /**
     * @param PaymentResponse[] $paymentResponces
     */
    public function setPaymentResponces($paymentResponces) {
        $this->paymentResponces = $paymentResponces;
    }

}