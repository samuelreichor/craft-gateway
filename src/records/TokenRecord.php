<?php

namespace samuelreichor\gateway\records;

use craft\db\ActiveRecord;
use samuelreichor\gateway\Constants;
use samuelreichor\gateway\models\GatewaySchema;
use yii\db\ActiveQueryInterface;

/**
 * Token record
 * @property int $id ID
 * @property int $schemaId Schema ID
 * @property string $name Token name
 * @property string $accessToken The access token
 * @property bool $enabled whether the token is enabled
 * @property GatewaySchema $scope Scope
 */
class TokenRecord extends ActiveRecord
{
    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return Constants::TABLE_TOKENS;
    }

    /**
     * Returns the token's schema.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getSchema(): ActiveQueryInterface
    {
        return $this->hasOne(GatewaySchema::class, ['id' => 'schemaId']);
    }
}
