<?php

namespace Wucdbm\Component\Epay\Client;

use Wucdbm\Component\Epay\Exception\ChecksumMismatchException;
use Wucdbm\Component\Epay\Exception\EasyPayGetIdnError;
use Wucdbm\Component\Epay\Exception\InvoiceNotFoundException;
use Wucdbm\Component\Epay\Exception\NoDataException;
use Wucdbm\Component\Epay\Payment\PaymentParams;
use Wucdbm\Component\Epay\Response\ChecksumMismatchResponse;
use Wucdbm\Component\Epay\Response\EasyPayResponse;
use Wucdbm\Component\Epay\Response\ErrorResponse;
use Wucdbm\Component\Epay\Response\MissingPaymentResponse;
use Wucdbm\Component\Epay\Response\NoDataResponse;
use Wucdbm\Component\Epay\Response\PaymentResponse;
use Wucdbm\Component\Epay\Response\ReceiveResponse;
use Wucdbm\Component\Epay\Response\ReceiveResponseInterface;
use Wucdbm\Component\Epay\Response\SuccessResponse;

class EpayClient {

    const SUBMIT_URL_EPAY_PROD = 'https://www.epay.bg/',
        SUBMIT_URL_EPAY_TEST = 'https://demo.epay.bg/';

    const SUBMIT_URL_EASY_PAY_PROD = 'https://www.epay.bg/ezp/reg_bill.cgi',
        SUBMIT_URL_EASY_PAY_TEST = 'https://demo.epay.bg/ezp/reg_bill.cgi';

    const PAYMENT_CHECKSUM_KEY = 'checksum',
        PAYMENT_ENCODED_KEY = 'encoded';

    protected $options;

    /**
     * @var PaymentHandlerInterface
     */
    protected $handler;

    /**
     * EpayClient constructor.
     * @param ClientOptions $options
     * @param PaymentHandlerInterface $handler
     */
    public function __construct(ClientOptions $options, PaymentHandlerInterface $handler) {
        $this->options = $options;
        $this->handler = $handler;
    }

    public function getEasyPayRequestUrl($encoded, $checksum) {
        $data = [
            'encoded'  => $encoded,
            'checksum' => $checksum
        ];

        return $this->getEasyPayUrl() . '?' . http_build_query($data);
    }

    public function getEasyPayUrl() {
        return $this->options->getEasyPayUrl();
    }

    public function getSubmitUrl() {
        return $this->options->getSubmitUrl();
    }

    public function getSubmitButtonHtml() {
        return $this->options->getSubmitButton();
    }

    public function getMerchantId() {
        return $this->options->getMerchantId();
    }

    public function getMerchantSecret() {
        return $this->options->getMerchantSecret();
    }

    /**
     * @param $invoiceId
     * @param $amount
     * @param $description
     * @param \DateTime $expiryDate
     * @param string $formId
     * @param $okUrl
     * @param $cancelUrl
     * @return string
     */
    public function getEpayForm($invoiceId, $amount, $description, \DateTime $expiryDate, $formId = '', $okUrl, $cancelUrl) {
        $exp_date = $expiryDate->format('d.m.Y');
        $merchantId = $this->getMerchantId();

        $data = <<<DATA
MIN={$merchantId}
INVOICE={$invoiceId}
AMOUNT={$amount}
EXP_TIME={$exp_date}
DESCR={$description}
ENCODING=utf-8
DATA;

        $encoded = base64_encode($data);
        $checksum = $this->hmac($encoded, $this->getMerchantSecret());

        $form = '<form action="' . $this->getSubmitUrl() . '" method="POST" name="' . $formId . '" id="' . $formId . '">
                <input type="hidden" name="PAGE" value="paylogin">
                <input type="hidden" name="ENCODED" value="' . $encoded . '">
                <input type="hidden" name="CHECKSUM" value="' . $checksum . '">
                <input type="hidden" name="URL_OK" value="' . $okUrl . '">
                <input type="hidden" name="URL_CANCEL" value="' . $cancelUrl . '">
                ' . $this->getSubmitButtonHtml() . '
                </form>';

        return $form;
    }

    /**
     * @param $invoiceId
     * @param $amount
     * @param $description
     * @param \DateTime $expiryDate
     * @return EasyPayResponse
     * @throws EasyPayGetIdnError
     */
    public function getEasyPayIdn($invoiceId, $amount, $description, \DateTime $expiryDate) {
        $exp_date = $expiryDate->format('d.m.Y');
        $merchantId = $this->getMerchantId();

        $data = <<<DATA
MIN={$merchantId}
INVOICE={$invoiceId}
AMOUNT={$amount}
EXP_TIME={$exp_date}
DESCR={$description}
ENCODING=utf-8
DATA;

        $encoded = base64_encode($data);
        $checksum = $this->hmac($encoded, $this->getMerchantSecret());
        $url = $this->getEasyPayRequestUrl($encoded, $checksum);

        $body = file_get_contents($url);

        if (strpos($body, 'IDN=') === 0) {
            $idn = str_replace('IDN=', '', $body);

            return new EasyPayResponse($body, $idn, '', false);
        }

        throw new EasyPayGetIdnError($body);
    }

    public function getEasyPayFakePayUrl($idn) {
        return sprintf('https://demo.epay.bg/ezp/pay_bill.cgi?ACTION=PAY&IDN=%s', $idn);
    }

    /**
     * @param array $post
     * @return ReceiveResponseInterface
     */
    public function receiveResponse(array $post) {
        try {
            return $this->receive($post);
        } catch (NoDataException $ex) {
            $this->handler->onError($ex);

            return $this->createNoDataResponse();
        } catch (ChecksumMismatchException $ex) {
            $this->handler->onError($ex);

            return $this->createInvalidChecksumResponse();
        }
    }

    /**
     * @param $post
     * @return ReceiveResponse
     * @throws ChecksumMismatchException
     * @throws NoDataException
     */
    public function receive(array $post) {
        if (!isset($post[self::PAYMENT_CHECKSUM_KEY]) || !isset($post[self::PAYMENT_ENCODED_KEY])) {
            throw new NoDataException('No data.');
        }

        $checksum = $post[self::PAYMENT_CHECKSUM_KEY];
        $encoded = $post[self::PAYMENT_ENCODED_KEY];
        $check = $this->hmac($encoded, $this->getMerchantSecret());
        $decoded = base64_decode($encoded);

        $checksumMatches = $check == $checksum;

        $this->handler->onReceive($encoded, $decoded, $checksum, $checksumMatches);

        if (!$checksumMatches) {
            throw new ChecksumMismatchException('Checksum mismatch.');
        }

        return $this->handleLines($decoded);
    }

    public function handleLines($lines) {
        $responses = [];
        foreach (explode("\n", $lines) as $line) {
            if ($line) {
                $responses[] = $this->handlePayment($line);
            }
        }

        return new ReceiveResponse($responses);
    }

    /**
     * @param $line
     * @return PaymentResponse
     */
    public function handlePayment($line) {
        $params = new PaymentParams($line);

        try {
            $this->handler->onPayment($params);
        } catch (InvoiceNotFoundException $ex) {
            return new MissingPaymentResponse($params->getInvoice());
        }

        # XXX if OK for this invoice
        # $info_data .= "INVOICE=$invoice:STATUS=OK\n";

        # XXX if error for this invoice
        # XXX $info_data .= "INVOICE=$invoice:STATUS=ERR\n";

        # XXX if not recognise this invoice
        # XXX $info_data .= "INVOICE=$invoice:STATUS=NO\n";

        if (PaymentParams::STATUS_PAID == $params->getStatus()) {
            $response = $this->handler->handlePaid($params);
        } elseif (PaymentParams::STATUS_DENIED == $params->getStatus()) {
            $response = $this->handler->handleDenied($params);
        } elseif (PaymentParams::STATUS_EXPIRED == $params->getStatus()) {
            $response = $this->handler->handleExpired($params);
        } else {
            return new ErrorResponse($params->getInvoice());
        }

        if ($response instanceof PaymentResponse) {
            return $response;
        }

        return new SuccessResponse($params->getInvoice());
    }

    /**
     * @return NoDataResponse
     */
    public function createNoDataResponse() {
        return new NoDataResponse();
    }

    /**
     * @return ChecksumMismatchResponse
     */
    public function createInvalidChecksumResponse() {
        return new ChecksumMismatchResponse();
    }

    public function hmac($data, $passwd) {
        return $this->_hmac($this->options->getHmacAlgo(), $data, $passwd);
    }

    /**
     * @param $algo
     * @param $data
     * @param $passwd
     * @return mixed
     */
    private function _hmac($algo, $data, $passwd) {
        /* md5 and sha1 only */
        $algo = strtolower($algo);
        $p = array('md5' => 'H32', 'sha1' => 'H40');
        if (strlen($passwd) > 64) $passwd = pack($p[$algo], $algo($passwd));
        if (strlen($passwd) < 64) $passwd = str_pad($passwd, 64, chr(0));

        $ipad = substr($passwd, 0, 64) ^ str_repeat(chr(0x36), 64);
        $opad = substr($passwd, 0, 64) ^ str_repeat(chr(0x5C), 64);

        return ($algo($opad . pack($p[$algo], $algo($ipad . $data))));
    }

}
