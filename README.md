# Epay library for the epay.bg payment gateway

## While there are no docs for this library you can check the code of https://packagist.org/packages/wucdbm/epay-bundle to get a clue how to use this

## TODO / Upcoming
- In EpayClient::receiveResponse one should be able to mail himself those two exceptions or do something before the respective response is returned
- Not sure if we really need that iconv at line 139 in EpayClient
- Maybe throw an exception instead of returning a response with a isError boolean for EasyPay