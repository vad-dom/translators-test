<?php

namespace frontend\modules\api\controllers;

use common\services\TranslatorService;
use Yii;
use yii\web\Controller;

class TranslatorController extends Controller
{
    public $enableCsrfValidation = false;

    private TranslatorService $service;

    public function __construct($id, $module, TranslatorService $service, $config = [])
    {
        $this->service = $service;
        parent::__construct($id, $module, $config);
    }

    public function beforeAction($action)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return parent::beforeAction($action);
    }

    public function actionList(): array
    {
        return ['items' => $this->service->listTranslators()];
    }

    public function actionCreate(): array
    {
        $body = Yii::$app->request->getBodyParams();
        $t = $this->service->createTranslator(
            (string)($body['name'] ?? ''),
            (int)($body['work_mode'] ?? 0),
            (string)($body['bookable_until'] ?? '')
        );
        return [
            'ok' => true,
            'item' => [
                'id' => $t->id,
                'name' => $t->name,
                'work_mode' => $t->work_mode,
                'bookable_until' => $t->bookable_until
            ],
        ];
    }

    public function actionDelete(int $id): array
    {
        $this->service->deleteTranslator($id);
        return ['ok' => true];
    }

    public function actionCalendar(int $id, int $offset = 0, int $limit = TranslatorService::DATES_PAGE_SIZE): array
    {
        return $this->service->getCalendarChunk($id, $offset, $limit);
    }

    public function actionBook(): array
    {
        $body = Yii::$app->request->getBodyParams();
        $translatorId = (int)($body['translator_id'] ?? 0);
        $date = (string)($body['date'] ?? '');

        try {
            $this->service->bookDay($translatorId, $date);
            return ['ok' => true];
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'DATE_ALREADY_BUSY') {
                Yii::$app->response->statusCode = 409;
                return [
                    'ok' => false,
                    'code' => 'DATE_ALREADY_BUSY',
                    'message' => 'Кто-то уже занял эту дату. Обновите страницу и попробуйте снова.'
                ];
            }
            throw $e;
        }
    }

    public function actionUnbook(): array
    {
        $body = Yii::$app->request->getBodyParams();
        $count = $this->service->unbookDay((int)($body['translator_id'] ?? 0), (string)($body['date'] ?? ''));
        return ['ok' => true, 'deleted' => $count];
    }

    public function actionAvailability(string $date): array
    {
        return ['phrase' => $this->service->availabilityPhrase($date)];
    }
}