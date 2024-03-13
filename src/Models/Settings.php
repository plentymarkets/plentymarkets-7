<?php

namespace Payone\Models;


use Carbon\Carbon;
use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;
use Plenty\Modules\Plugin\DataBase\Contracts\Model;

/**
 * Class Settings
 *
 * @property int $id
 * @property int $clientId
 * @property int $pluginSetId
 * @property array $value
 * @property string $createdAt
 * @property string $updatedAt
 *
 *
 * @package Payone\Models
 */
class Settings extends Model
{
    public $id;
    public $clientId;
    public $pluginSetId;
    public $value = [];
    public $createdAt = '';
    public $updatedAt = '';

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return 'Payone::settings';
    }

    /**
     * @param array $data
     * @return Model
     */
    public function create(array $data): Model
    {
        $this->clientId = $data['clientId'];
        $this->pluginSetId = $data['pluginSetId'];
        $this->createdAt = (string)Carbon::now();

        $this->value = [
            'loginId' => $data['loginId'] ?? ''
        ];

        return $this->save();
    }

    /**
     * @param string $settingKey
     * @return array|mixed|null
     */
    public function getValue(string $settingKey = "")
    {
        if (!empty($settingKey)) {
            return $this->value[$settingKey] ?? null;
        }

        return $this->value;
    }

    /**
     * @param array $data
     * @return Model
     */
    public function updateValues(array $data): Model
    {
        if (isset($data['loginId'])) {
            $this->value['loginId'] = $data['loginId'];
        }
        return $this->save();
    }

    /**
     * @param Settings $newModel
     * @return Model
     */
    public function save(): Model
    {
        /** @var DataBase $database */
        $database = pluginApp(DataBase::class);
        $this->updatedAt = (string)Carbon::now();

        return $database->save($this);
    }

    /**
     * @return bool
     */
    public function delete(): bool
    {
        /** @var DataBase $database */
        $database = pluginApp(DataBase::class);
        return $database->delete($this);
    }
}
