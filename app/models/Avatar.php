<?php

use fuss\FussFile;

class Avatar extends Model
{
    const AVATAR_IMAGE_WIDTH = 210;
    const AVATAR_IMAGE_HEIGHT = 210;
    const AVATAR_IMAGE_EXTENSION = 'jpg';
    const AVATAR_MAX_SIZE = 2097152; //2M

    protected $service = 'fuss';

    public static function save($imagePath, $imageType, $x, $y, $w, $h)
    {
        $fussFile = array(
            'content' => self::createImage($imagePath, $imageType, $x, $y, $w, $h),
            'extension' => self::AVATAR_IMAGE_EXTENSION,
            'category' => ''
        );
        return self::factory()->call('avatar_upload')->with(new FussFile($fussFile))->run();
    }

    public static function createImage($imagePath, $imageType, $x, $y, $w, $h)
    {
        $newImageResource = imagecreatetruecolor($w, $h);
        $imageResource = imagecreatefromstring(file_get_contents($imagePath));
        imagecopy($newImageResource, $imageResource, 0, 0, $x, $y, $w, $h);

        $newResizedImageResource = imagecreatetruecolor(self::AVATAR_IMAGE_WIDTH, self::AVATAR_IMAGE_HEIGHT);
        imagecopyresampled($newResizedImageResource, $newImageResource, 0, 0, 0, 0, self::AVATAR_IMAGE_WIDTH, self::AVATAR_IMAGE_HEIGHT, $w, $h);

        ob_start();
        if ($imageType === 'jpeg') {
            imagejpeg($newResizedImageResource, null, 100);
        }
        if ($imageType === 'png') {
            imagepng($newResizedImageResource, null, 0);
        }
        if ($imageType === 'gif') {
            imagegif($newResizedImageResource);
        }
        $newResizedImageData = ob_get_clean();

        return $newResizedImageData;
    }

    public static function checkSize($size)
    {
        return $size <= self::AVATAR_MAX_SIZE;
    }
}
