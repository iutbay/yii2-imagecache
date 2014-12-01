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
