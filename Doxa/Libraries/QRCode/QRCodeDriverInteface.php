<?php

namespace Doxa\Libraries\QRCode;

interface QRCodeDriverInteface
{
    public function generate($params = [], $format = '', $filepath = '');
}
