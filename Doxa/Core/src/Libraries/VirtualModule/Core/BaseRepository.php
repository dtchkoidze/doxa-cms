<?php

// 📁 Doxa/Core/Libraries/VirtualModule/Core/BaseRepository.php

namespace Doxa\Core\Libraries\VirtualModule\Core;

use Doxa\Core\Libraries\VirtualModule\Contracts\RepositoryInterface;

abstract class BaseRepository extends VirtualModel implements RepositoryInterface
{
    public function afterSave(): void {}
    public function docs(): string
    {
        $path = base_path("Modules/{$this->package->module}/Docs/" . app()->getLocale() . ".php");
        return file_exists($path) ? file_get_contents($path) : 'No docs found';
    }
}