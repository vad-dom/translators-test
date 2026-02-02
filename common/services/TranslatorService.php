<?php

namespace common\services;

use common\models\Translator;
use common\models\TranslatorBooking;
use DateTimeImmutable;
use RuntimeException;
use Yii;
use yii\db\Exception;
use yii\db\IntegrityException;
use yii\db\StaleObjectException;

class TranslatorService
{
    public const DATES_PAGE_SIZE = 30;

    /**
     * @return array
     */
    public function listTranslators(): array
    {
        return Translator::find()->orderBy(['name' => SORT_ASC])->asArray()->all();
    }

    /**
     * @param string $name
     * @param int $workMode
     * @param string $bookableUntil
     * @return Translator
     * @throws Exception
     */
    public function createTranslator(string $name, int $workMode, string $bookableUntil): Translator
    {
        $t = new Translator();
        $t->name = trim($name);
        $t->work_mode = $workMode;
        $t->bookable_until = $bookableUntil;

        if (!$t->save()) {
            throw new RuntimeException(
                'Validation error: ' . json_encode($t->errors, JSON_UNESCAPED_UNICODE)
            );
        }
        return $t;
    }

    /**
     * @param int $id
     * @return void
     * @throws \Throwable
     * @throws StaleObjectException
     */
    public function deleteTranslator(int $id): void
    {
        $t = Translator::findOne($id);
        if (!$t) {
            return;
        }
        $t->delete();
    }

    /**
     * @param int $translatorId
     * @param int $offsetDays
     * @param int $limitDays
     * @return array
     * @throws \Exception
     */
    public function getCalendarChunk(int $translatorId, int $offsetDays, int $limitDays = self::DATES_PAGE_SIZE): array
    {
        $t = Translator::findOne($translatorId);
        if (!$t) {
            throw new RuntimeException('Translator not found');
        }

        $today = (new DateTimeImmutable('today'))->format('Y-m-d');
        $start = (new DateTimeImmutable($today))->modify("+{$offsetDays} days");
        $end = $start->modify('+' . ($limitDays - 1) . ' days');

        $bookableUntil = new DateTimeImmutable($t->bookable_until);
        if ($start > $bookableUntil) {
            return [
                'translator' => $this->translatorToArray($t),
                'dates' => [],
                'is_end' => true,
            ];
        }
        if ($end > $bookableUntil) {
            $end = $bookableUntil;
        }

        $busyDates = TranslatorBooking::find()
            ->select(['date'])
            ->where(['translator_id' => $translatorId])
            ->andWhere(['between', 'date', $start->format('Y-m-d'), $end->format('Y-m-d')])
            ->indexBy('date')
            ->asArray()
            ->all();

        $dates = [];
        $cur = $start;
        while ($cur <= $end) {
            $d = $cur->format('Y-m-d');
            $isWeekend = $this->isWeekend($d);

            $allowedByMode =
                ($t->work_mode === Translator::MODE_WORKING_DAYS && !$isWeekend) ||
                ($t->work_mode === Translator::MODE_DAYS_OFF && $isWeekend);

            $dates[] = [
                'date' => $d,
                'is_weekend' => $isWeekend,
                'allowed' => $allowedByMode,
                'busy' => isset($busyDates[$d]),
            ];
            $cur = $cur->modify('+1 day');
        }

        $isEnd = ($end->format('Y-m-d') >= $t->bookable_until);

        return [
            'translator' => $this->translatorToArray($t),
            'dates' => $dates,
            'is_end' => $isEnd,
        ];
    }

    /**
     * @param int $translatorId
     * @param string $date
     * @return void
     * @throws Exception
     */
    public function bookDay(int $translatorId, string $date): void
    {
        $sql = 'INSERT INTO {{%translator_booking}} (translator_id, `date`)
                VALUES (:translator_id, :date)';

        try {
            Yii::$app->db->createCommand($sql, [
                ':translator_id' => $translatorId,
                ':date' => $date,
            ])->execute();
        } catch (IntegrityException $e) {
            throw new RuntimeException('DATE_ALREADY_BUSY');
        }
    }

    /**
     * @param int $translatorId
     * @param string $date
     * @return int
     */
    public function unbookDay(int $translatorId, string $date): int
    {
        return TranslatorBooking::deleteAll(['translator_id' => $translatorId, 'date' => $date]);
    }

    /**
     * @param string $date
     * @return string
     * @throws \Exception
     */
    public function availabilityPhrase(string $date): string
    {
        $isWeekend = $this->isWeekend($date);

        $sql = 'SELECT t.*
                FROM {{%translator}} t
                LEFT JOIN {{%translator_booking}} b
                  ON b.translator_id = t.id AND b.`date` = :date
                WHERE b.id IS NULL
                  AND :date <= t.bookable_until
                  AND (
                    (:isWeekend = 0 AND t.work_mode = :wmWeekdays)
                    OR
                    (:isWeekend = 1 AND t.work_mode = :wmWeekends)
                  )
                LIMIT 1';

        $row = Yii::$app->db->createCommand($sql, [
            ':date' => $date,
            ':isWeekend' => $isWeekend ? 1 : 0,
            ':wmWeekdays' => Translator::MODE_WORKING_DAYS,
            ':wmWeekends' => Translator::MODE_DAYS_OFF,
        ])->queryOne();

        return $row ? 'Список переводчиков готов' : 'Нет свободных переводчиков';
    }

    /**
     * @param string $date
     * @return bool
     * @throws \Exception
     */
    private function isWeekend(string $date): bool
    {
        $dt = new DateTimeImmutable($date);
        $n = (int)$dt->format('N');
        return $n >= 6;
    }

    /**
     * @param Translator $t
     * @return array
     */
    private function translatorToArray(Translator $t): array
    {
        return [
            'id' => $t->id,
            'name' => $t->name,
            'work_mode' => $t->work_mode,
            'bookable_until' => $t->bookable_until,
        ];
    }
}