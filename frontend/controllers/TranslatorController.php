<?php

namespace frontend\controllers;

use yii\web\Controller;

class TranslatorController extends Controller
{
    public function actionIndex()
    {
        return $this->render('index');
    }
}