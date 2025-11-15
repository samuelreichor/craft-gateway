<?php

namespace samuelreichor\gateway\records;

use craft\db\ActiveRecord;
use samuelreichor\gateway\Constants;

/**
 * Schema record
 * @property int $id ID
 * @property string $name Schema name
 * @property array $scope The scope of the schema
 */
class SchemaRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return Constants::TABLE_SCHEMAS;
    }
}
