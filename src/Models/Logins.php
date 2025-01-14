<?php

namespace Payone\Models;

class Logins
{
    public int|null $id;
    public string $key;
    public string $invoiceSecureKey;

    /**
     * @param int|null $id
     * @param string $key
     * @param string $invoiceSecureKey
     */
    public function __construct(int $id = null, string $key = '', string $invoiceSecureKey = '')
    {
        $this->id = $id;
        $this->key = $key;
        $this->invoiceSecureKey = $invoiceSecureKey;
    }
}
