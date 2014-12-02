<?php

namespace iutbay\yii2imagecache;

use Yii;
use yii\imagine\Image;
use yii\helpers\Html;

use Imagine\Image\ManipulatorInterface;

/**
 * ImageCache Component
 * @author Kevin LEVRON <kevin.levron@gmail.com>
 */
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
    public $sourceUrl;
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

        if (!isset($this->sourcePath) || !isset($this->sourceUrl))
            throw new \yii\base\InvalidConfigException('Invalid sourcePath/sourceUrl.');

        if (!isset($this->thumbsPath) || !isset($this->thumbsUrl)) {
            $this->thumbsPath = '@app/web/thumbs';
            $this->thumbsUrl = '@web/thumbs';
        }

        $this->sourcePath = Yii::getAlias($this->sourcePath);
        $this->sourceUrl = Yii::getAlias($this->sourceUrl);
        $this->thumbsPath = Yii::getAlias($this->thumbsPath);
        $this->thumbsUrl = Yii::getAlias($this->thumbsUrl);
    }
    
    /**
     * Get thumb img tag
     * @param string $path
     * @param string $size
     * @return string html img
     */
    public function thumb($path, $size = self::SIZE_THUMB, $imgOptions = [])
    {
        return Html::img(self::thumbSrc($path, $size), $imgOptions);
    }

    /**
     * Get thumb src
     * @param string $path
     * @param string $size
     * @return string html img
     */
    public function thumbSrc($path, $size = self::SIZE_THUMB)
    {
        if (!isset($this->sizes[$size]))
            throw new \InvalidArgumentException('Unkown size '.$size);

        $realPath = str_replace($this->sourceUrl, $this->sourcePath, $path);
        if (!file_exists($realPath) || !preg_match('#^(.*)\.('.$this->getExtensionsRegexp().')$#', $path, $matches))
            throw new \InvalidArgumentException('Invalid path '.$realPath);

        $suffix = $this->getSufixFromSize($size);
        $src = "{$matches[1]}{$suffix}.{$matches[2]}";
        $src = str_replace($this->sourceUrl, $this->thumbsUrl, $src);
        return $src;
    }

    /**
     * Create thumb
     * @param string $path thumb path
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
     * @param string $path thumb path
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
        $thumb = Image::thumbnail($srcPath, $width, $height, $mode);

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
     * Get size from suffix
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

    /**
     * Get suffix from size
     * @param string $size
     * @return string suffix
     */
    private function getSufixFromSize($size)
    {
        if (!empty($this->sizeSuffixes[$size]))
            return $this->sizeSuffixes[$size];

        else return $this->defaultSizeSuffix . $size;
    }

}
