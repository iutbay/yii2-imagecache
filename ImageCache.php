<?php

namespace iutbay\yii2imagecache;

class ImageCache extends \yii\base\Component
{

    const SIZE_THUMB = 'thumb';
    const SIZE_MEDIUM = 'medium';
    const SIZE_LARGE = 'large';
    const SIZE_FULL = 'full';

    public $sizes = [
        self::SIZE_THUMB => [150, 150],
        self::SIZE_MEDIUM => [300, 300],
        self::SIZE_LARGE => [600, 600],
    ];
    
    public $sourcePath;
    public $thumbsPath;
    public $thumbsUrl;

    public $defaultPathPrefix = '_';
    public $pathPrefixes = [
        self::SIZE_THUMB => '',
        self::SIZE_MEDIUM => '',
        self::SIZE_LARGE => '',
    ];

    public $extensions = [
        'jpg' => 'jpeg',
        'jpeg' => 'jpeg',
        'png' => 'png',
        'gif' => 'gif',
        'bmp' => 'bmp',
    ];

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        
        if (!isset($this->sourcePath))
            throw new \yii\base\InvalidConfigException('Invalid sourcePath.');
        
        if (!isset($this->thumbsPath)) {
            $this->thumbsPath = Yii::getAlias('@app/web/thumbs');
            $this->thumbsUrl = Yii::getAlias('@web/thumbs');
        }
    }
    
    /**
     * Create image
     * @param string $path image path
     * @param boolean $overwrite
     * @return boolean
     */
    public function create($path, $overwrite = true)
    {
    }
    
    /**
     * Output image to browser
     * @param string $path
     * @throws HttpException
     */
    public function output($path)
    {
    }

}
