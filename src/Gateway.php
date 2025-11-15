<?php

namespace samuelreichor\gateway;

use Craft;
use craft\base\Event;
use craft\base\Model;
use craft\base\Plugin;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\services\UserPermissions;
use craft\web\UrlManager;
use samuelreichor\gateway\models\Settings;
use samuelreichor\gateway\services\SchemaService;
use samuelreichor\gateway\services\TokenService;
use samuelreichor\gateway\twigextensions\AuthHelper;
use Throwable;
use yii\log\FileTarget;

/**
 * Gateway plugin
 *
 * @method static Gateway getInstance()
 * @method Settings getSettings()
 * @author Samuel Reichör <samuelreichor@gmail.com>
 * @copyright Samuel Reichör
 * @license https://craftcms.github.io/license/ Craft License
 *
 * @property SchemaService $schema
 * @property TokenService $token
 */
class Gateway extends Plugin
{
    public string $schemaVersion = '1.0.0';
    public bool $hasCpSettings = true;
    public bool $hasCpSection = true;

    public static function config(): array
    {
        return [
            'components' => [
                'schema' => new SchemaService(),
                'token' => new TokenService(),
            ],
        ];
    }

    public function init(): void
    {
        parent::init();

        $this->_initLogger();
        $this->_registerConfigListeners();

        if (Craft::$app->getRequest()->getIsCpRequest()) {
            $this->_registerCpRoutes();
            $this->_registerCpTwigExtensions();
            $this->_registerPermissions();
        }
    }

    /**
     * @throws Throwable
     */
    public function getCpNavItem(): ?array
    {
        $item = parent::getCpNavItem();
        $subNavs = [];
        $isAllowedAdminChanges = Craft::$app->getConfig()->getGeneral()->allowAdminChanges;
        $currentUser = Craft::$app->getUser()->getIdentity();
        if ($currentUser->can(Constants::EDIT_SCHEMAS) && $isAllowedAdminChanges) {
            $subNavs['schemas'] = [
                'label' => 'Schemas',
                'url' => 'gateway/schemas',
            ];
        }

        if ($currentUser->can(Constants::EDIT_TOKENS)) {
            $subNavs['tokens'] = [
                'label' => 'Tokens',
                'url' => 'gateway/tokens',
            ];
        }

        if (empty($subNavs)) {
            return null;
        }

        if (count($subNavs) <= 1) {
            return array_merge($item, [
                'subnav' => [],
            ]);
        }

        return array_merge($item, [
            'subnav' => $subNavs,
        ]);
    }

    private function _initLogger(): void
    {
        $logFileTarget = new FileTarget([
            'logFile' => '@storage/logs/gateway.log',
            'maxLogFiles' => 10,
            'categories' => ['gateway'],
            'logVars' => [],
        ]);
        Craft::getLogger()->dispatcher->targets[] = $logFileTarget;
    }

    private function _registerPermissions(): void
    {
        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function(RegisterUserPermissionsEvent $event) {
                $event->permissions[] = [
                    'heading' => 'Gateway',
                    'permissions' => [
                        Constants::EDIT_SCHEMAS => [
                            'label' => 'Manage Schemas',
                        ],
                        Constants::EDIT_TOKENS => [
                            'label' => 'Manage Tokens',
                        ],
                    ],
                ];
            }
        );
    }

    private function _registerCpRoutes(): void
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $urlRules = [];

            $isAllowedAdminChanges = Craft::$app->getConfig()->getGeneral()->allowAdminChanges;
            $currentUser = Craft::$app->getUser()->getIdentity();

            // Cp request but no valid sessionId.
            if (!$currentUser) {
                return;
            }

            $canEditSchemas = $currentUser->can(Constants::EDIT_SCHEMAS) && $isAllowedAdminChanges;
            $canEditTokens = $currentUser->can(Constants::EDIT_TOKENS);

            if ($canEditSchemas) {
                $urlRules['gateway'] = ['template' => 'gateway/schemas/_index.twig'];
                $urlRules['gateway/schemas'] = ['template' => 'gateway/schemas/_index.twig'];
                $urlRules['gateway/schemas/new'] = 'gateway/schema/edit-schema';
                $urlRules['gateway/schemas/<schemaId:\d+>'] = 'gateway/schema/edit-schema';
            }

            if ($canEditTokens) {
                $urlRules['gateway/tokens'] = ['template' => 'gateway/tokens/_index.twig'];
                $urlRules['gateway/tokens/new'] = 'gateway/token/edit-token';
                $urlRules['gateway/tokens/<tokenId:\d+>'] = 'gateway/token/edit-token';

                if (!$canEditSchemas) {
                    $urlRules['gateway'] = ['template' => 'gateway/tokens/_index.twig'];
                }
            }

            $event->rules = array_merge($event->rules, $urlRules);
        });
    }

    private function _registerCpTwigExtensions(): void
    {
        Craft::$app->view->registerTwigExtension(new AuthHelper());
    }

    private function _registerConfigListeners(): void
    {
        Craft::$app->getProjectConfig()
            ->onAdd(Constants::PATH_SCHEMAS . '.{uid}', $this->_proxy('schema', 'handleChangedSchema'))
            ->onUpdate(Constants::PATH_SCHEMAS . '.{uid}', $this->_proxy('schema', 'handleChangedSchema'))
            ->onRemove(Constants::PATH_SCHEMAS . '.{uid}', $this->_proxy('schema', 'handleDeletedSchema'));
    }

    protected function createSettingsModel(): ?Model
    {
        return Craft::createObject(Settings::class);
    }

    protected function settingsHtml(): ?string
    {
        return Craft::$app->view->renderTemplate('gateway/_settings.twig', [
            'plugin' => $this,
            'settings' => $this->getSettings(),
        ]);
    }

    /**
     * Returns a proxy function for calling a component method, based on its ID.
     *
     * The component won’t be fetched until the method is called, avoiding unnecessary component instantiation, and ensuring the correct component
     * is called if it happens to get swapped out (e.g. for a test).
     *
     * @param string $id The component ID
     * @param string $method The method name
     * @return callable
     */
    private function _proxy(string $id, string $method): callable
    {
        return function() use ($id, $method) {
            return $this->get($id)->$method(...func_get_args());
        };
    }
}
