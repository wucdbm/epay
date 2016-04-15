# Epay

Usage

- Create a `Wucdbm\Component\Epay\Client\ClientOptions` instance, responsible for providing all the options the `Wucdbm\Component\Epay\Client\Client` needs. Its constructor needs the merchant ID and secret, provided by epay.bg. Remember to set the third constructor parameter to true or false depending on whether you're testing or not.
- Create a handler that implements the `Wucdbm\Component\Epay\Client\PaymentHandlerInterface` interface. This interface has methods that are being called by the client
- Create an instanceo of `Wucdbm\Component\Epay\Client\Client`, passing the above two
- To create a payment form, call `$client->getEpayForm($id, $amount, $description, $expiry, $formId, $okUrl, $cancelUrl)`
- To create an EasyPay payment ID, call `$response = $this->client->getEasyPayIdn($id, $amount, $description, $expiry)`. This will return a `Wucdbm\Component\Epay\Response\EasyPayResponse` response. Call `$response->getIdn()` to get the ID. Beware, this method throws a `Wucdbm\Component\Epay\Exception\EasyPayGetIdnError` exception.

The WucdbmEpayBundle https://packagist.org/packages/wucdbm/epay-bundle uses this library, head there for more examples.

```
$options = new \Wucdbm\Component\Epay\Client\ClientOptions($merchantId, $merchantSecret, $isDebug);
// $options->set... to alter any options
$handler = new \My\Project\Payments\Epay\PaymentHandler(LogManager $some, PaymentManager $dependencies);
$client = new \Wucdbm\Component\Epay\Client\Client($options, $handler);
```

Getting a payment form is done like this

```
$uniqId = uniqid(); // this is to make autosubmitting easy - $('#formId').submit();
$expiry = new \DateTime('today + 2 days');
// ... other parameters
$form = $client->getEpayForm($id, $amount, $description, $expiry, $formId, $okUrl, $cancelUrl);
// Display the form and optionally auto submit it. Another option is to alter the submit button through the options and let the user do that
```

Getting an EasyPay IDN

```
$id = $payment->getId();
$amount = $payment->getAmount();
$description = 'Payment #' . $payment->getId();
$expiry = $payment->getExpiryDate();

try {
    $response = $this->client->getEasyPayIdn($id, $amount, $description, $expiry);
    $idn = $response->getIdn();
    // display idn to the user, do some logging.
    // the response body is available at $response->getBody();
    return $idn;
} catch (\Wucdbm\Component\Epay\Exception\EasyPayGetIdnError $ex) {
    $this->logEasyPayRequest($payment, $ex->getBody(), true);
    throw $ex;
}
```

Receiving payments

```
$response = $client->receiveResponse($post);
// the client will call your handler, which must deal with the payments received
// $response is an \Wucdbm\Component\Epay\Response\ReceiveResponseInterface instance
// exceptions are caught internally and transformed into responses, the no data exception and checksum mismatch exceptions in particular generate a global error response for epay
echo $response->toString();
exit();
// alternatively, if you use Symfony's HttpFoundation component
$response = \Symfony\Component\HttpFoundation\Response($response->toString());
```