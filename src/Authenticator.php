<?php declare(strict_types=1);

namespace Room11\OpenId;

interface Authenticator
{
    public function logIn(string $url, Credentials $credentials);
}
