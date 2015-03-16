<?php

namespace iutbay\yii2imagecache;

use Yii;
use yii\imagine\Image;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;

use Imagine\Image\Color;
use Imagine\Image\ManipulatorInterface;
use Imagine\Image\Point;

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

    public $sourcePath;
    public $sourceUrl;
    public $thumbsPath;
    public $thumbsUrl;
    public $resizeMode;
    public $sizes = [
        self::SIZE_THUMB => [150, 150],
        self::SIZE_MEDIUM => [300, 300],
        self::SIZE_LARGE => [600, 600],
    ];
    public $defaultSizeSuffix = '_';
    public $sizeSuffixes = [];
    //public $checkPathRegexp = '#[/-a-z0-9_\.]*#i';

    public $extensions = [
        'jpg' => 'jpeg',
        'jpeg' => 'jpeg',
        'png' => 'png',
        'gif' => 'gif',
        'bmp' => 'bmp',
    ];
    public $text;

    //public $watermark;

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

        if (isset($this->text)) {
            if (!isset($this->text['text']) || !isset($this->text['fontFile']))
                throw new \yii\base\InvalidConfigException('Invalid text.');
        }

        $this->sourcePath = Yii::getAlias($this->sourcePath);
        $this->sourceUrl = Yii::getAlias($this->sourceUrl);
        $this->thumbsPath = Yii::getAlias($this->thumbsPath);
        $this->thumbsUrl = Yii::getAlias($this->thumbsUrl);

        $this->sourcePath = str_replace('\\', '/', $this->sourcePath);
        $this->thumbsPath = str_replace('\\', '/', $this->thumbsPath);

        if (!isset($this->resizeMode))
            $this->resizeMode = ManipulatorInterface::THUMBNAIL_OUTBOUND;
    }

    /**
     * Get thumb img tag
     * @param string $path
     * @param string $size
     * @return string
     */
    public function thumb($path, $size = self::SIZE_THUMB, $imgOptions = [])
    {
        return Html::img(self::thumbSrc($path, $size), $imgOptions);
    }

    /**
     * Get thumb src
     * @param string $path
     * @param string $size
     * @return string
     */
    public function thumbSrc($path, $size = self::SIZE_THUMB)
    {
        $path = Yii::getAlias($path);

        if ($size != self::SIZE_FULL && !isset($this->sizes[$size]))
            throw new \yii\base\InvalidParamException('Unkown size ' . $size);

        $realPath = str_replace($this->sourceUrl, $this->sourcePath, $path);
        if (!file_exists($realPath) || !preg_match('#^(.*)\.(' . $this->getExtensionsRegexp() . ')$#', $path, $matches))
            throw new \yii\base\InvalidParamException('Invalid path ' . $path);

        $suffix = $this->getSufixFromSize($size);
        $src = "{$matches[1]}{$suffix}.{$matches[2]}";
        $src = str_replace($this->sourceUrl, $this->thumbsUrl, $src);
        $src = str_replace('%', '%25', $src);
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
        return $this->createThumb($info['srcPath'], $info['dstPath'], $info['size'], $this->resizeMode);
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
        if ($size == self::SIZE_FULL) {
            $thumb = Image::getImagine()->open($srcPath);
        } else {
            $width = $this->sizes[$size][0];
            $height = $this->sizes[$size][1];
            $thumb = Image::thumbnail($srcPath, $width, $height, $mode);
        }

        if (isset($this->text)) {
            $fontOptions = ArrayHelper::getValue($this->text, 'fontOptions', []);
            $fontSize = ArrayHelper::getValue($fontOptions, 'size', 12);
            $fontColor = ArrayHelper::getValue($fontOptions, 'color', 'fff');
            $fontAngle = ArrayHelper::getValue($fontOptions, 'angle', 0);
            $start = ArrayHelper::getValue($this->text, 'start', [0, 0]);

            $font = Image::getImagine()->font(Yii::getAlias($this->text['fontFile']), $fontSize, new Color($fontColor));
            $thumb->draw()->text($this->text['text'], $font, new Point($start[0], $start[1]), $fontAngle);
        }

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
        } else if (preg_match('#^(.*)\.(' . $this->getExtensionsRegexp() . ')$#', $path, $matches)) {
            return [
                'size' => self::SIZE_FULL,
                'srcPath' => $this->sourcePath . '/' . $matches[1] . '.' . $matches[2],
                'dstPath' => $this->thumbsPath . '/' . $path,
                'extension' => $matches[2],
            ];
        }
    }

    /**
     * Get size suffixes regexp
     * @return string regexp
     */
    private function getSizeSuffixesRegexp()
    {
        return join('|', $this->getSizeSuffixes());
    }

    /**
     * Get extensions regexp
     * @return string regexp
     */
    public function getExtensionsRegexp()
    {
        $keys = array_keys($this->extensions);
        return '(?i)' . join('|', $keys);
    }

    /**
     * Get size from suffix
     * @param string $suffix
     * @return string size
     */
    private function getSizeFromSuffix($suffix)
    {
        return array_search($suffix, $this->getSizeSuffixes());
    }

    /**
     * Get suffix from size
     * @param string $size
     * @return string suffix
     */
    private function getSufixFromSize($size)
    {
        return ArrayHelper::getValue($this->getSizeSuffixes(), $size);
    }

    private function getSizeSuffixes()
    {
        $suffixes = [];
        foreach ($this->sizes as $size => $sizeConf) {
            $suffixes[$size] = ArrayHelper::getValue($this->sizeSuffixes, $size, $this->defaultSizeSuffix . $size);
        }
        return $suffixes;
    }

}
