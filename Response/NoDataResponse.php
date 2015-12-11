<?php

namespace Wucdbm\Component\Epay\Response;

class NoDataResponse implements ReceiveResponseInterface {

    public function toString() {
        return 'ERR=No Data';
    }

}