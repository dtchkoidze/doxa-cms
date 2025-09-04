<?php

namespace Doxa\Libraries\QRCode\drivers;

use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Doxa\Libraries\QRCode\QRCodeDriverInteface;

class SimpleSoftwareIO implements QRCodeDriverInteface
{
    protected $formats = ['png', 'eps', 'svg'];

    public function generate($params = [], $format = '', $filepath = '')
    {
        $qr = QrCode::errorCorrection('H');

        if(empty($params['text'])){
            dd('Text not provided');
        }

        if(!empty($params['size'])){
            $qr->size($params['size']);
        }

        if(!empty($params['margin'])){
            $qr->margin($params['margin']);
        }

        if(!empty($params['bgColor'])){
            $rgb = sscanf($params['bgColor'], "#%02x%02x%02x");
            $qr->backgroundColor($rgb[0],$rgb[1],$rgb[2]);
        }

        if(!empty($params['fgColor'])){
            $rgb = sscanf($params['fgColor'], "#%02x%02x%02x");
            $qr->color(...$rgb);
        }

        if($format){
            if(!in_array($format, $this->formats)){
                dd('Invalid format '.$format.'. Available formats: '.implode(', ', $this->formats));
            }
            $qr->format($format);
        }

        // $result = $qr->generate($params['text'], $filepath);

        // dd($result);

        return $qr->generate($params['text'], $filepath);
    }
}
