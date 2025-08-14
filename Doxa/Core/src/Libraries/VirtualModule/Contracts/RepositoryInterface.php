<?php

// 📁 Doxa/Core/Libraries/VirtualModule/Contracts/RepositoryInterface.php

namespace Doxa\Core\Libraries\VirtualModule\Contracts;

interface RepositoryInterface
{
    public function afterSave(): void;
    public function docs(): string;
}
