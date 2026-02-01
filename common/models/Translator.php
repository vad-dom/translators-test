<?php

namespace common\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $name
 * @property int $work_mode
 * @property string $bookable_until Y-m-d
 *
 * @property TranslatorBooking[] $bookings
 */
class Translator extends ActiveRecord
{
    public const MODE_WORKING_DAYS = 1;
    public const MODE_DAYS_OFF = 2;

    public static function tableName(): string
    {
        return '{{%translator}}';
    }

    public function rules(): array
    {
        return [
            [['name', 'work_mode', 'bookable_until'], 'required'],
            ['name', 'string', 'max' => 255],
            ['work_mode', 'in', 'range' => [self::MODE_WORKING_DAYS, self::MODE_DAYS_OFF]],
            ['bookable_until', 'date', 'format' => 'php:Y-m-d'],
        ];
    }

    public function getBookings(): ActiveQuery
    {
        return $this->hasMany(TranslatorBooking::class, ['translator_id' => 'id']);
    }
}