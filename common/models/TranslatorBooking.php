<?php

namespace common\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $translator_id
 * @property string $date Y-m-d
 *
 * @property Translator $translator
 */
class TranslatorBooking extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%translator_booking}}';
    }

    public function rules(): array
    {
        return [
            [['translator_id', 'date'], 'required'],
            ['translator_id', 'integer'],
            ['date', 'date', 'format' => 'php:Y-m-d'],
            [
                ['translator_id', 'date'],
                'unique',
                'targetAttribute' => ['translator_id', 'date'],
                'message' => 'Дата уже занята',
            ],
        ];
    }

    public function getTranslator(): ActiveQuery
    {
        return $this->hasOne(Translator::class, ['id' => 'translator_id']);
    }
}