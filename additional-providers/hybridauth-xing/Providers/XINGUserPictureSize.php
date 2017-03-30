<?php

/**
 * XingUserPicureSize
 *
 * List of images available in the XING api for a user profile
 */
class XingUserPicureSize
{
    const SIZE_32X32 = 'size_32x32';
    const SIZE_48X48 = 'size_48x48';
    const SIZE_64X64 = 'size_64x64';
    const SIZE_96X96 = 'size_96x96';
    const SIZE_128X128 = 'size_128x128';
    const SIZE_192X192 = 'size_192x192';
    const SIZE_256X256 = 'size_256x256x';
    const SIZE_1024X1024 = 'size_1024x1024';
    const SIZE_ORIGINAL = 'size_original';

    public static function getImageType( $picureSize = null )
    {
        return $picureSize !== null ? $picureSize : self::SIZE_192X192;
    }
}


