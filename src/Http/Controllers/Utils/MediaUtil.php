<?php

namespace Iquesters\UserManagement\Http\Controllers\Utils;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MediaUtil
{
    const DEFAULT_FILE_UPLOAD_OPTIONS = [];

    public static function getMediaType($file_url)
    {
        // Get the MIME type
        $mimeType = mime_content_type($file_url);
        return $mimeType;
    }

    public static function storeFile($file, $location = null, $fileOptions = [])
    {
        $options = [...MediaUtil::DEFAULT_FILE_UPLOAD_OPTIONS, ...$fileOptions];
        // return Storage::putFile('media' . $location, $file, $options);
        return Storage::disk('public')->putFile('media' . $location, $file, $options);

    }

    public static function getFile($filename)
    {
        Log::info('filename=' . $filename);
        $contents = null;
        // Check if the file exists
        if (Storage::disk('media')->exists($filename)) {
            // Get the file contents
            $contents = Storage::disk('media')->get($filename);
        }
        return $contents;
    }
}
