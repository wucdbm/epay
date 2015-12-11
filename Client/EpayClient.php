<?php

namespace Wucdbm\Component\Epay\Client;

use GuzzleHttp\Client;
use Wucdbm\Component\Epay\Exception\ChecksumMismatchException;
use Wucdbm\Component\Epay\Exception\EasyPayRequestErrorException;
use Wucdbm\Component\Epay\Exception\InvoiceNotFoundException;
use Wucdbm\Component\Epay\Exception\NoDataException;
use Wucdbm\Component\Epay\Payment\PaymentParams;
use Wucdbm\Component\Epay\Response\ErrorResponse;
use Wucdbm\Component\Epay\Response\MissingPaymentResponse;
use Wucdbm\Component\Epay\Response\PaymentResponse;
use Wucdbm\Component\Epay\Response\ReceiveResponse;
use Wucdbm\Component\Epay\Response\SuccessResponse;

class EpayClient {

    const SUBMIT_URL_EPAY_PROD = 'https://www.epay.bg/',
        SUBMIT_URL_EPAY_TEST = 'https://demo.epay.bg/';

    const SUBMIT_URL_EASY_PAY_PROD = 'https://www.epay.bg/ezp/reg_bill.cgi',
        SUBMIT_URL_EASY_PAY_TEST = 'https://demo.epay.bg/ezp/reg_bill.cgi';

    const PAYMENT_CHECKSUM_KEY = 'checksum',
        PAYMENT_ENCODED_KEY = 'encoded';

    const HMAC_ALGO = 'sha1';

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
        $checksum = $this->hmac('sha1', $encoded, $this->getMerchantSecret());

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
     * @return mixed
     * @throws EasyPayRequestErrorException
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

        // TODO: Ditch Guzzle in favor of file_get_contents because guzzle v6 might not be installable everywhere?
        $encoded = base64_encode($data);
        $checksum = $this->hmac('sha1', $encoded, $this->getMerchantSecret());
        $url = $this->getEasyPayRequestUrl($encoded, $checksum);

        $client = new Client();
        $response = $client->request('GET', $url);

        $body = $response->getBody()->getContents();
        $body = iconv('cp1251', 'utf-8', $body);

        $matches = [];
        if (preg_match_all('/IDN=(?<idn>\d+)/', $body, $matches)) {
            if (isset($matches['idn'][0])) {

                return $matches['idn'][0];
            }
        }
        // TODO: Return EasyPayRequest object

        preg_match_all('/ERR=(?<err>.+)/', $body, $matches);
        $error = isset($matches['err'][0]) ? $matches['err'][0] : '';

        throw new EasyPayRequestErrorException($error);
    }

    public function getEasyPayFakePayUrl($idn) {
        return sprintf('https://demo.epay.bg/ezp/pay_bill.cgi?ACTION=PAY&IDN=%s', $idn);
    }

    public function receive($data) {
        if (!isset($data[self::PAYMENT_CHECKSUM_KEY]) || !isset($data[self::PAYMENT_ENCODED_KEY])) {
            throw new NoDataException();
        }

        $checksum = $data[self::PAYMENT_CHECKSUM_KEY];
        $encoded = $data[self::PAYMENT_ENCODED_KEY];
        $check = self::hmac(self::HMAC_ALGO, $encoded, $this->getMerchantSecret());
        $decoded = base64_decode($encoded);

        $checksumMatches = $check == $checksum;

        $this->handler->onReceive($encoded, $decoded, $checksum, $checksumMatches);

        if (!$checksumMatches) {
            throw new ChecksumMismatchException();
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

    private function hmac($algo, $data, $passwd) {
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
