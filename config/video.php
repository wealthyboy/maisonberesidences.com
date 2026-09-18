<?php

return [
    'disk' => env('VIDEO_DISK', 'spaces'),
    'source_directory' => env('VIDEO_SOURCE_DIRECTORY', 'videos/sources'),
    'max_upload_kilobytes' => (int) env('VIDEO_MAX_UPLOAD_KILOBYTES', 1048576),
];
