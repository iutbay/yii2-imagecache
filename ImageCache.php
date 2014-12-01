<?php

namespace iutbay\yii2imagecache;

use Yii;
use yii\web\HttpException;

use Imagine\Image\Box;
use Imagine\Image\Color;
use Imagine\Image\Point;
use Imagine\Image\ManipulatorInterface;

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
    public $defaultSizeSuffix = '_';
    public $sizeSuffixes = [
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

        $this->sourcePath = Yii::getAlias($this->sourcePath);

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
        // http://localhost/yii2-basic/web/thumbs/strain/white-widow/corey_thumb.jpg

        // test path
        $info = $this->getPathInfo($path);
        if (!is_array($info))
            return false;

        // check original image
        $srcPath = $this->sourcePath . '/' . $info['path'];
        if (!file_exists($srcPath))
            return false;

        // check destination folder
        $folder = preg_replace('#/[^/]*$#', '', $info['path']);
        $folder = $this->thumbsPath . '/' . $folder;
        if (!file_exists($folder))
            @mkdir($folder, 0777, true);

        // create image
        $dstPath = $this->thumbsPath . '/' . $path;
        return $this->createThumb($srcPath, $dstPath, $info['size']);
    }

    public function createThumb($srcPath, $dstPath, $size, $mode = ManipulatorInterface::THUMBNAIL_OUTBOUND)
    {
        var_dump(func_get_args());

        $width = $size[0];
        $height = $size[1];
//        $box = new Box($width, $height);
//        $img = static::getImagine()->load($data);
//
//        if (($img->getSize()->getWidth() <= $box->getWidth() && $img->getSize()->getHeight() <= $box->getHeight()) || (!$box->getWidth() && !$box->getHeight())) {
//            return $img->copy();
//        }
//
//        $img = $img->thumbnail($box, $mode);
//
//        // create empty image to preserve aspect ratio of thumbnail
//        $thumb = static::getImagine()->create($box, new Color('FFF', 100));
//
//        // calculate points
//        $size = $img->getSize();
//
//        $startX = 0;
//        $startY = 0;
//        if ($size->getWidth() < $width) {
//            $startX = ceil($width - $size->getWidth()) / 2;
//        }
//        if ($size->getHeight() < $height) {
//            $startY = ceil($height - $size->getHeight()) / 2;
//        }
//
//        $thumb->paste($img, new Point($startX, $startY));
//
//        return $thumb;

        return true;
    }

    /**
     * Output image to browser
     * @param string $path
     * @throws HttpException
     */
    public function output($path)
    {
        // test path
        $info = $this->getPathInfo($path);
        $path = $this->thumbsPath . '/' . $path;
        if (!is_array($info) || !file_exists($path))
            throw new HttpException(404, Yii::t('yii', 'Page not found.'));

        // send image to browser
        header('Content-type: image/' . $this->extensions[$info['extension']]);
        header('Content-Length: ' . filesize($path));
        readfile($path);
    }

    /**
     * Get size and original image path/extension from path
     * @param string $path
     * @return string size
     */
    private function getPathInfo($path)
    {
        $regexp = '#^(.*)(' . $this->getSizeSuffixesRegexp() . ')\.(' . $this->getExtensionsRegexp() . ')$#';
        if (preg_match($regexp, $path, $matches)) {
            return [
                'size' => $this->getSizeFromSuffix($matches[2]),
                'path' => $matches[1] . '.' . $matches[3],
                'extension' => $matches[3],
            ];
        }
    }

    /**
     * Get path suffixes regexp
     * @return string regexp
     */
    private function getSizeSuffixesRegexp()
    {
        $suffixes = [];
        foreach ($this->sizeSuffixes as $key => $val) {
            // skip full size
            if ($key === self::SIZE_FULL)
                continue;

            if (empty($val))
                $val = $this->defaultSizeSuffix . $key;
            $suffixes[] = $val;
        }
        return join('|', $suffixes);
    }

    /**
     * Get extensions regexp
     * @return string regexp
     */
    public function getExtensionsRegexp()
    {
        $keys = array_keys($this->extensions);
        return join('|', $keys);
    }

    /**
     * Get size from path suffix
     * @param string $suffix
     * @return string size
     */
    private function getSizeFromSuffix($suffix)
    {
        foreach ($this->sizeSuffixes as $key => $val) {
            if (empty($val))
                $val = $this->defaultSizeSuffix . $key;
            if ($val === $suffix)
                return $key;
        }
    }

}
