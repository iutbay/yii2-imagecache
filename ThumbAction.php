<?php

namespace iutbay\yii2imagecache;

use Yii;
use yii\web\HttpException;

class ThumbAction extends \yii\base\Action
{

    public function run($path)
    {
        var_dump($path);
        var_dump(Yii::$app->imageCache);

//        if (empty($path) || !Yii::$app->imageCache->create($path)) {
//            throw new HttpException(404, Yii::t('yii', 'Page not found.'));
//        } else {
//            Yii::$app->imageCache->output($path);
//            exit();
//        }
    }

}
