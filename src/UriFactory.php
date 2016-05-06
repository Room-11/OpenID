<?php declare(strict_types=1);

namespace Room11\OpenId;

use Amp\Artax\Uri;

class UriFactory
{
    public function build($uri)
    {
        return new Uri($uri);
    }
}
