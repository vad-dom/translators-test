<?php

namespace frontend\modules\api\controllers;

use common\services\TranslatorService;
use Yii;
use yii\base\InvalidConfigException;
use yii\db\Exception;
use yii\db\StaleObjectException;
use yii\web\BadRequestHttpException;
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

    /**
     * @param $action
     * @return bool
     * @throws BadRequestHttpException
     */
    public function beforeAction($action): bool
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return parent::beforeAction($action);
    }

    /**
     * @return array
     */
    public function actionList(): array
    {
        return ['items' => $this->service->listTranslators()];
    }

    /**
     * @return array
     * @throws InvalidConfigException
     * @throws Exception
     */
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

    /**
     * @param int $id
     * @return true[]
     * @throws \Throwable
     * @throws StaleObjectException
     */
    public function actionDelete(int $id): array
    {
        $this->service->deleteTranslator($id);
        return ['ok' => true];
    }

    /**
     * @param int $id
     * @param int $offset
     * @param int $limit
     * @return array
     * @throws \Exception
     */
    public function actionCalendar(int $id, int $offset = 0, int $limit = TranslatorService::DATES_PAGE_SIZE): array
    {
        return $this->service->getCalendarChunk($id, $offset, $limit);
    }

    /**
     * @return array|true[]
     * @throws Exception
     * @throws InvalidConfigException
     */
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

    /**
     * @return array
     * @throws InvalidConfigException
     */
    public function actionUnbook(): array
    {
        $body = Yii::$app->request->getBodyParams();
        $count = $this->service->unbookDay((int)($body['translator_id'] ?? 0), (string)($body['date'] ?? ''));
        return ['ok' => true, 'deleted' => $count];
    }

    /**
     * @param string $date
     * @return array
     * @throws \Exception
     */
    public function actionAvailability(string $date): array
    {
        return ['phrase' => $this->service->availabilityPhrase($date)];
    }
}