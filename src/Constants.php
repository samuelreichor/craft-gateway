<?php

namespace samuelreichor\gateway;

class Constants
{
    // Tables
    public const TABLE_SCHEMAS = '{{%gateway_schemas}}';
    public const TABLE_TOKENS = '{{%gateway_tokens}}';

    // Project Config
    public const PATH_SCHEMAS = 'gateway.schemas';

    // Permissions
    public const EDIT_SCHEMAS = 'gateway-schemas:edit';
    public const EDIT_TOKENS = 'gateway-tokens:edit';

    // Cache Identifier
    public const CACHE_TAG_GlOBAL = 'queryapi:global';

    // Base Transformer Settings
    public const EXCLUDED_FIELD_CLASSES = ['nystudio107\seomatic\fields\SeoSettings'];
}
