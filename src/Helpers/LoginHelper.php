<?php

namespace Payone\Helpers;

class LoginHelper
{
    /**
     * @param array $params
     * @return array
     */
    public function cleanupLogs(array $params)
    {
        array_walk_recursive($params, function (&$value, $key) {
            if ($key === 'key') {
                $value = '---';
            }
        });

        return $params;
    }
}