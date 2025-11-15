<?php

namespace samuelreichor\gateway\controllers;

use Craft;
use craft\web\Controller;
use Exception;
use samuelreichor\gateway\Constants;
use samuelreichor\gateway\models\GatewaySchema;
use samuelreichor\gateway\Gateway;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\MethodNotAllowedHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class SchemaController extends Controller
{
    /**
     * @param int|null $schemaId
     * @param GatewaySchema|null $schema
     * @return Response
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     */
    public function actionEditSchema(?int $schemaId = null, ?GatewaySchema $schema = null): Response
    {
        $this->requirePermission(Constants::EDIT_SCHEMAS);
        $general = Craft::$app->getConfig()->getGeneral();
        if (!$general->allowAdminChanges) {
            throw new ForbiddenHttpException('Unable to edit Gateway schemas because admin changes are disabled in this environment.');
        }

        if ($schema || $schemaId) {
            if (!$schema) {
                $schema = Gateway::getInstance()->schema->getSchemaById($schemaId);
            }

            if (!$schema) {
                throw new NotFoundHttpException('Schema not found');
            }

            $title = trim($schema->name) ?: Craft::t('app', 'Edit Gateway Schema');
            $usage = Gateway::getInstance()->token->getSchemaUsageInTokens($schema->id);
        } else {
            $schema = new GatewaySchema();
            $title = trim($schema->name) ?: Craft::t('app', 'Create a new Gateway Schema');
            $usage = [];
        }

        return $this->renderTemplate('gateway/schemas/_edit.twig', compact(
            'schema',
            'title',
            'usage',
        ));
    }

    /**
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws Exception
     */
    public function actionSaveSchema(): ?Response
    {
        $this->requirePermission(Constants::EDIT_SCHEMAS);
        $this->requirePostRequest();
        $this->requireElevatedSession();

        $schemaService = Gateway::getInstance()->schema;
        $schemaId = $this->request->getBodyParam('schemaId');

        if ($schemaId) {
            $schema = $schemaService->getSchemaById($schemaId);

            if (!$schema) {
                throw new NotFoundHttpException('Schema not found');
            }
        } else {
            $schema = new GatewaySchema();
        }

        $schema->name = $this->request->getBodyParam('name') ?? $schema->name;
        $schema->scope = $this->request->getBodyParam('permissions') ?? [];

        if (!$schemaService->saveSchema($schema)) {
            $this->setFailFlash(Craft::t('app', 'Couldn’t save schema.'));

            // Send the schema back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'schema' => $schema,
            ]);

            return null;
        }

        $this->setSuccessFlash(Craft::t('app', 'Schema saved.'));
        return $this->redirectToPostedUrl($schema);
    }

    /**
     * @return Response
     * @throws BadRequestHttpException
     * @throws MethodNotAllowedHttpException
     * @throws ForbiddenHttpException
     */
    public function actionDeleteSchema(): Response
    {
        $this->requirePermission(Constants::EDIT_SCHEMAS);
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $schemaId = $this->request->getRequiredBodyParam('id');

        Gateway::getInstance()->schema->deleteSchemaById($schemaId);

        return $this->asSuccess();
    }
}
