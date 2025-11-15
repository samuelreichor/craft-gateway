<?php

namespace samuelreichor\gateway\controllers;

use Craft;
use craft\web\Controller;
use Exception;
use InvalidArgumentException;
use samuelreichor\gateway\Constants;
use samuelreichor\gateway\models\GatewayToken;
use samuelreichor\gateway\Gateway;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\MethodNotAllowedHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class TokenController extends Controller
{
    /**
     * @param int|null $tokenId
     * @param GatewayToken|null $token
     * @return Response
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws \yii\base\Exception
     * @throws Exception
     */
    public function actionEditToken(?int $tokenId = null, ?GatewayToken $token = null): Response
    {
        $this->requirePermission(Constants::EDIT_TOKENS);

        $tokenService = Gateway::getInstance()->token;
        $accessToken = null;

        if ($token || $tokenId) {
            if (!$token) {
                $token = $tokenService->getTokenById($tokenId);
            }

            if (!$token) {
                throw new NotFoundHttpException('Token not found');
            }

            $title = trim($token->name) ?: Craft::t('app', 'Edit Gateway Token');
        } else {
            $token = new GatewayToken();
            $accessToken = $tokenService->generateToken();
            $title = trim($token->name) ?: Craft::t('app', 'Create a new Gateway token');
        }

        $schemas = Gateway::getInstance()->schema->getSchemas();
        $schemaOptions = [];

        foreach ($schemas as $schema) {
            $schemaOptions[] = [
                'label' => $schema->name,
                'value' => $schema->id,
            ];
        }

        if ($token->id && !$token->schemaId && !empty($schemaOptions)) {
            // Add a blank option to the top so it's clear no schema is currently selected
            array_unshift($schemaOptions, [
                'label' => '',
                'value' => '',
            ]);
        }

        return $this->renderTemplate('gateway/tokens/_edit.twig', compact(
            'token',
            'title',
            'accessToken',
            'schemaOptions'
        ));
    }

    /**
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws Exception
     */
    public function actionSaveToken(): ?Response
    {
        $this->requirePermission(Constants::EDIT_TOKENS);
        $this->requirePostRequest();
        $this->requireElevatedSession();

        $tokenService = Gateway::getInstance()->token;
        $tokenId = $this->request->getBodyParam('tokenId');

        if ($tokenId) {
            $token = $tokenService->getTokenById($tokenId);

            if (!$token) {
                throw new NotFoundHttpException('Token not found');
            }
        } else {
            $token = new GatewayToken();
        }

        $token->name = $this->request->getBodyParam('name') ?? $token->name;
        $token->accessToken = $this->request->getBodyParam('accessToken') ?? $token->accessToken;
        $token->enabled = (bool)$this->request->getRequiredBodyParam('enabled');
        $token->schemaId = $this->request->getBodyParam('schema');

        if (!$tokenService->saveToken($token)) {
            $this->setFailFlash(Craft::t('app', 'Couldn’t save token.'));

            // Send the schema back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'token' => $token,
            ]);

            return null;
        }

        $this->setSuccessFlash(Craft::t('app', 'Token saved.'));
        return $this->redirectToPostedUrl($token);
    }

    /**
     * @return Response
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws MethodNotAllowedHttpException
     */
    public function actionDeleteToken(): Response
    {
        $this->requirePermission(Constants::EDIT_TOKENS);
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $tokenId = $this->request->getRequiredBodyParam('id');

        Gateway::getInstance()->token->deleteTokenById($tokenId);

        return $this->asSuccess();
    }

    /**
     * @return Response
     * @throws BadRequestHttpException
     * @throws MethodNotAllowedHttpException
     * @throws ForbiddenHttpException
     */
    public function actionFetchToken(): Response
    {
        $this->requirePermission(Constants::EDIT_TOKENS);
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $this->requireElevatedSession();

        $tokenUid = $this->request->getRequiredBodyParam('tokenUid');

        try {
            $token = Gateway::getInstance()->token->getTokenByUid($tokenUid);
        } catch (InvalidArgumentException) {
            throw new BadRequestHttpException('Invalid token UID.');
        }

        return $this->asJson([
            'accessToken' => $token->accessToken,
        ]);
    }

    /**
     * @return Response
     * @throws MethodNotAllowedHttpException
     * @throws ForbiddenHttpException
     * @throws BadRequestHttpException
     * @throws Exception
     */
    public function actionGenerateToken(): Response
    {
        $this->requirePermission(Constants::EDIT_TOKENS);
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        return $this->asJson([
            'accessToken' => Gateway::getInstance()->token->generateToken(),
        ]);
    }
}
