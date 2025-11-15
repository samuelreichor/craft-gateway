<?php

namespace samuelreichor\gateway\controllers;

use Craft;
use craft\elements\Entry;
use craft\errors\ElementNotFoundException;
use craft\web\Controller;
use Throwable;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\web\MethodNotAllowedHttpException;
use yii\web\Response;

class EntriesController extends Controller
{
    protected array|bool|int $allowAnonymous = true;
    public $enableCsrfValidation = false;

    /**
     * @throws InvalidConfigException
     * @throws MethodNotAllowedHttpException
     */
    public function actionRead(): Response
    {
        $this->requirePostRequest();

        $body = Craft::$app->getRequest()->getBodyParams();

        if(!key_exists('queryParams', $body)) {
            return $this->asJson([
                'error' => "Please provide query params via the queryParams key in the body."
            ]);
        }
        $queryParams = $body['queryParams'];
        $entryQuery = Entry::find()
            ->section('*');

        foreach ($queryParams as $queryParam => $value) {
            if ($entryQuery->hasMethod($queryParam)) {
                $entryQuery->$queryParam = $value;
            }
        }

        return $this->asJson($entryQuery->all());
    }

    /**
     * @throws Throwable
     * @throws ElementNotFoundException
     * @throws InvalidConfigException
     * @throws MethodNotAllowedHttpException
     * @throws Exception
     */
    public function actionEdit(int $entryId): Response
    {
        $this->requirePostRequest();
        $values = Craft::$app->getRequest()->getBodyParams();

        $entry = Entry::find()
            ->id($entryId)
            ->one();

        if (!$entry) {
            return $this->asJson([
                'error' => "Entry with ID {$entryId} not found."
            ]);
        }

        if (isset($values['title'])) {
            $entry->title = $values['title'];
        }
        foreach ($values as $fieldHandle => $value) {
            if ($entry->hasField($fieldHandle)) {
                $entry->setFieldValue($fieldHandle, $value);
            }
        }
        if (!Craft::$app->elements->saveElement($entry)) {
            return $this->asJson([
                'error' => `Entry with ID {$entryId} not saved due to error.`,
                'validationErrors' => $entry->getErrors(),
            ]);
        }

        return $this->asJson([
            'entryId' => `Entry with ID {$entry->id} saved successfully.`,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function actionDelete(int $entryId): Response
    {
        $entry = Entry::findOne($entryId);
        if (!$entry) {
            return $this->asJson(['success' => true, 'message' => 'Entry not found, already deleted..']);
        }

        if (!Craft::$app->elements->deleteElement($entry)) {
            return $this->asJson(['success' => false, 'message' => 'Unable to delete entry with id ' . $entryId]);
        }

        return $this->asJson(['success' => true, 'message' => 'Entry with id ' . $entryId . ' deleted successfully']);
    }
}
