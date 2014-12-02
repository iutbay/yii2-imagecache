<?php

namespace iutbay\yii2imagecache;

use Yii;
use yii\web\HttpException;

class ThumbAction extends \yii\base\Action
{

    public function run($path)
    {
        if (empty($path) || !Yii::$app->imageCache->output($path)) {
            throw new HttpException(404, Yii::t('yii', 'Page not found.'));
        }
    }

}
