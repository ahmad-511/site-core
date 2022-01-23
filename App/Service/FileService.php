<?php
declare (strict_types = 1);

namespace App\Service;

use App\Core\Service;

class FileService extends Service
{
    public static function GenerateFileName(string $prefix = 'fl_', int $refID): string{
        return $prefix . $refID . md5($refID . date('Y-m-d H:i:s') . random_int(1, 99999));
    }
}