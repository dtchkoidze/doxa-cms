<?php

// 📁 Doxa/Core/Libraries/VirtualModule/Helpers/RepositoryFactory.php

namespace Doxa\Core\Libraries\VirtualModule\Helpers;

use Doxa\Core\Libraries\VirtualModule\Core\BaseRepository;

class RepositoryFactory
{
    public static function make(string $module): BaseRepository
    {
        $customClass = "Projects\\Gpg\\Modules\\" . ucfirst($module) . "\\Repositories\\" . ucfirst($module);

        if (class_exists($customClass)) {
            return new $customClass();
        }

        $baseClass = new class($module) extends BaseRepository {
        };

        return $baseClass;
    }
}
