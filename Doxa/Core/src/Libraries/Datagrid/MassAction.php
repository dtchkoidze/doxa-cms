<?php

namespace Doxa\Core\Libraries\Datagrid;

/**
 * Initial implementation of the mass action class. Stay tuned for more features coming soon.
 */
class MassAction
{
    /**
     * Create a column instance.
     */
    public function __construct(
        public string $icon,
        public string $title,
        public string $method,
        public mixed $action,
        public mixed $url,
        public array $options = [],
        public array $confirmation = [],
    ) {
    }
}
