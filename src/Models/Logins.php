<?php

namespace Payone\Models;

class Logins
{
    public int|null $id;
    public string $key;
    public string $invoiceSecureKey;

    /**
     * @param int|null $id
     * @param $key
     * @param $invoiceSecureKey
     */
    public function __construct(?int $id, $key, $invoiceSecureKey)
    {
        $this->id = $id;
        $this->key = $key;
        $this->invoiceSecureKey = $invoiceSecureKey;
    }
}
