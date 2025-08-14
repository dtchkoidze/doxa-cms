<?php

// 📁 Doxa/Core/Libraries/VirtualModule/Core/Traits/HasVariations.php

namespace Doxa\Core\Libraries\VirtualModule\Core\Traits;

trait HasVariations
{
    public function getVariations(): array
    {
        return $this->package->getEditingVariationFields();
    }
}
