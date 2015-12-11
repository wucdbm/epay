<?php

namespace Wucdbm\Component\Epay\Response;

class MissingPaymentResponse extends PaymentResponse {

    public function __construct($invoice) {
        parent::__construct($invoice, self::VALUE_MISSING_PAYMENT);
    }

}