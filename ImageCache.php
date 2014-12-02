<?php

namespace iutbay\yii2imagecache;

use Yii;
use yii\web\HttpException;
use yii\imagine\Image;

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
     * Create thumb
     * @param string $path image path
     * @param boolean $overwrite
     * @return boolean
     */
    public function create($path, $overwrite = true)
    {
        // test path
        $info = $this->getPathInfo($path);
        if (!is_array($info))
            return false;

        // check original image
        if (!file_exists($info['srcPath']))
            return false;

        // check destination folder
        $folder = preg_replace('#/[^/]*$#', '', $info['dstPath']);
        if (!file_exists($folder))
            @mkdir($folder, 0777, true);

        // create thumb
        return $this->createThumb($info['srcPath'], $info['dstPath'], $info['size']);
    }

    /**
     * Output thumb to browser
     * @param string $path
     */
    public function output($path)
    {
        // test path
        $info = $this->getPathInfo($path);
        if (!is_array($info) || (!file_exists($info['dstPath']) && !$this->create($path)))
            return false;

        // send image to browser
        header('Content-type: image/' . $this->extensions[$info['extension']]);
        header('Content-Length: ' . filesize($info['dstPath']));
        readfile($info['dstPath']);
        exit();
    }

    /**
     * Create thumb
     * @param string $srcPath
     * @param string $dstPath
     * @param string $size
     * @param string $mode
     * @return boolean
     */
    public function createThumb($srcPath, $dstPath, $size, $mode = ManipulatorInterface::THUMBNAIL_OUTBOUND)
    {
        $width = $this->sizes[$size][0];
        $height = $this->sizes[$size][1];
        $thumb = Image::thumbnail($srcPath, $width, $height);

        if ($thumb && $thumb->save($dstPath))
            return true;

        return false;
    }

    /**
     * Get info from path
     * @param string $path
     * @return null|array
     */
    private function getPathInfo($path)
    {
        $regexp = '#^(.*)(' . $this->getSizeSuffixesRegexp() . ')\.(' . $this->getExtensionsRegexp() . ')$#';
        if (preg_match($regexp, $path, $matches)) {
            return [
                'size' => $this->getSizeFromSuffix($matches[2]),
                'srcPath' => $this->sourcePath . '/' . $matches[1] . '.' . $matches[3],
                'dstPath' => $this->thumbsPath . '/' . $path,
                'extension' => $matches[3],
            ];
        }
    }

    /**
     * Get size suffixes regexp
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
