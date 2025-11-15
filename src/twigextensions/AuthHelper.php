<?php

namespace samuelreichor\gateway\twigextensions;

use craft\helpers\UrlHelper;
use craft\i18n\Formatter;
use samuelreichor\gateway\Gateway;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use yii\base\InvalidConfigException;

class AuthHelper extends AbstractExtension
{
    public function getName(): string
    {
        return 'Helper to get data in schema and token index pages';
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getSchemaComponents', $this->getSchemaComponents(...)),
            new TwigFunction('getAllSchemas', $this->getAllSchemas(...)),
            new TwigFunction('getAllTokens', $this->getAllTokens(...)),
        ];
    }

    /**
     * Returns all schema components
     */
    public function getSchemaComponents(): array
    {
        return Gateway::getInstance()->schema->getSchemaComponents();
    }

    /**
     * Returns all schemas
     */
    public function getAllSchemas(): array
    {
        $schemas = Gateway::getInstance()->schema->getSchemas();

        return array_map(function($schema) {
            return [
                'id' => $schema->id,
                'title' => $schema->name,
                'url' => UrlHelper::url('gateway/schemas/' . $schema->id),
                'usage' => Gateway::getInstance()->token->getSchemaUsageInTokensAmount($schema->id),
            ];
        }, $schemas);
    }

    public function getAllTokens(): array
    {
        $tokens = Gateway::getInstance()->token->getTokens();

        return array_map(/**
         * @throws InvalidConfigException
         */ function($schema) {
            return [
                'id' => $schema->id,
                'title' => $schema->name,
                'status' => $schema->enabled,
                'dateCreated' => (new Formatter())->asDate($schema->dateCreated),
                'dateUpdated' => (new Formatter())->asDate($schema->dateUpdated),
                'url' => UrlHelper::url('gateway/tokens/' . $schema->id),
            ];
        }, $tokens);
    }
}
