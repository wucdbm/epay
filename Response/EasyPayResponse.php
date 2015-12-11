<?php

namespace Wucdbm\Component\Epay\Response;

class EasyPayResponse {

    protected $body;

    protected $idn;

    protected $error;

    protected $isError;

    /**
     * EasyPayResponse constructor.
     * @param $body
     * @param $idn
     * @param $error
     * @param $isError
     */
    public function __construct($body, $idn, $error, $isError) {
        $this->body = $body;
        $this->idn = $idn;
        $this->error = $error;
        $this->isError = $isError;
    }

    /**
     * @return mixed
     */
    public function getBody() {
        return $this->body;
    }

    /**
     * @param mixed $body
     */
    public function setBody($body) {
        $this->body = $body;
    }

    /**
     * @return mixed
     */
    public function getIdn() {
        return $this->idn;
    }

    /**
     * @param mixed $idn
     */
    public function setIdn($idn) {
        $this->idn = $idn;
    }

    /**
     * @return mixed
     */
    public function getError() {
        return $this->error;
    }

    /**
     * @param mixed $error
     */
    public function setError($error) {
        $this->error = $error;
    }

    /**
     * @return mixed
     */
    public function getIsError() {
        return $this->isError;
    }

    /**
     * @param mixed $isError
     */
    public function setIsError($isError) {
        $this->isError = $isError;
    }

}