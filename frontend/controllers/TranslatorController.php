<?php

namespace frontend\controllers;

use yii\web\Controller;

class TranslatorController extends Controller
{
    public function actionIndex(): string
    {
        return $this->render('index');
    }
}