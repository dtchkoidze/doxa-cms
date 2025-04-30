<?php

namespace Doxa\Core\Cron;

use Doxa\Libraries\Utils;
use Illuminate\Support\Facades\File;
use Doxa\Core\Libraries\Logging\Clog;

class LogsCleanup
{
    public function __invoke()
    {
        Clog::write('cleanup', '>>>>>>>>>>>>>>>>>>>>>> LogsCleanup() <<<<<<<<<<<<<<<<<<<<<<<<', 4);

        $logs_lifetime = Utils::getLogsLifetime();
        $files = File::allFiles(storage_path('logs'));
        foreach($files as $file){
            $age = time() - $file->getMTime();
            if($age > $logs_lifetime){
                $re = File::delete($file->getPathname());
                if($re){
                    Clog::write('cleanup', 'Deleted '.$file->getPathname(), 4);
                }
            }
        }
    }
}
