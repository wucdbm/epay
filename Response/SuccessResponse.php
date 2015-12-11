<?php

namespace Wucdbm\Component\Epay\Response;

class SuccessResponse extends PaymentResponse {

    public function __construct($invoice) {
        parent::__construct($invoice, self::VALUE_OK);
    }

}