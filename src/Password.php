<?php declare(strict_types=1);

namespace Room11\OpenId;

class Password
{
    private $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function __toString()
    {
        return $this->value;
    }
}
