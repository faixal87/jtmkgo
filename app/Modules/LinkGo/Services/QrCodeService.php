<?php

namespace App\Modules\LinkGo\Services;

use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrCodeService
{
    public function svg(string $url, int $size = 320): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle($size, 2),
            new SvgImageBackEnd()
        );

        return (new Writer($renderer))->writeString($url);
    }

    public function png(string $url, int $size = 640): string
    {
        $renderer = new GDLibRenderer($size, 3, 'png');

        return (new Writer($renderer))->writeString($url);
    }
}
