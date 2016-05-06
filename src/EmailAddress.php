<?php declare(strict_types=1);

namespace Room11\OpenId;

class EmailAddress
{
    private $value;

    public function __construct(string $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidEmailAddressException();
        }

        $this->value = $value;
    }

    public function __toString()
    {
        return $this->value;
    }
}
