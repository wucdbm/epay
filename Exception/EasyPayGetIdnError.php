<?php

namespace Wucdbm\Component\Epay\Exception;

class EasyPayGetIdnError extends \Exception {

    protected $body;

    protected $error;

    public function __construct($body) {
        $this->body = $body;

        $error = $body;
        if (strpos($body, 'ERR=') === 0) {
            $error = str_replace('ERR=', '', $body);
        }

        parent::__construct(sprintf('Getting EasyPay IDN failed with error: "%s"', $error));
    }

    /**
     * @return string
     */
    public function getBody() {
        return $this->body;
    }

    /**
     * @return mixed
     */
    public function getError() {
        return $this->error;
    }

}