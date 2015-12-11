<?php

namespace Wucdbm\Component\Epay\Client;

use Wucdbm\Component\Epay\Exception\InvoiceNotFoundException;
use Wucdbm\Component\Epay\Payment\PaymentParams;
use Wucdbm\Component\Epay\Response\PaymentResponse;

interface PaymentHandlerInterface {

    /**
     * @param $encoded string
     * @param $decoded string
     * @param $checksum string
     * @param $checksumMatches boolean
     * @return void
     */
    public function onReceive($encoded, $decoded, $checksum, $checksumMatches);

    /**
     * @param PaymentParams $params
     * @throws InvoiceNotFoundException
     * @return void
     */
    public function onPayment(PaymentParams $params);

    /**
     * @param PaymentParams $params
     * @return PaymentResponse|null
     */
    public function handlePaid(PaymentParams $params);

    /**
     * @param PaymentParams $params
     * @return PaymentResponse|null
     */
    public function handleDenied(PaymentParams $params);

    /**
     * @param PaymentParams $params
     * @return PaymentResponse|null
     */
    public function handleExpired(PaymentParams $params);


}