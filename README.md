ImageCache for Yii2
===================

Like the Image module in Drupal, this extension will resize your images on demand :-).
If a thumb doesn't exist, the web server's rewrite rules will pass the request to Yii which in turn hands it off to ImageCache to dynamically generate the file.

WIP...

Installation
------------
The preferred way to install this helper is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require "iutbay/yii2-imagecache" "*"
```

or add

```json
"iutbay/yii2-imagecache" : "*"
```

to the require section of your application's `composer.json` file.

Configuration
-------------
You should :
* Add `ThumbAction` in one of your controller.
* Modify your application configuration :
  * add _imageCache_ component,
  * add url rule to handle request to missing thumbs.

### Add _ThumbAction_
You need to add `ThumbAction` in one of your controller so that imageCache can handle requests to missing thumbs and create them on demand. You could use `site` controller :
```php
class SiteController extends Controller
{
  ...
  public function actions()
  {
      return [
        ...
        'thumb' => 'iutbay\yii2imagecache\ThumbAction',
        ...
      ];
  }
  ...
}
```

### _imageCache_ component config
You should add _imageCache_ component in your application configuration :
```php
$config = [
    'components' => [
      ...
      'imageCache' => [
        'class' => 'iutbay\yii2imagecache\ImageCache',
        'sourcePath' => '@app/web/images',
        //'thumbsPath' => '@app/web/thumbs',
        //'thumbsUrl' => '@web/thumbs',
      ],
      ...
    ]
];
```

### _urlManager_ config
```php
$config = [
    'components' => [
      ...
      'urlManager' => [
        'enablePrettyUrl' => true,
        'showScriptName' => false,
        'rules' => [
          ...
          'thumbs/<path:.*>' => 'site/thumb',
          ...
        ],
      ],
      ...
    ]
];
```
