<?php

namespace Wucdbm\Component\Epay\Response;

class ChecksumMismatchResponse implements ReceiveResponseInterface {

    public function toString() {
        return 'ERR=Not valid CHECKSUM';
    }

}