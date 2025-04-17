<?php

namespace Doxa\Core\Libraries\Datagrid;

/**
 * Initial implementation of the action class. Stay tuned for more features coming soon.
 */
class Action
{
    /**
     * Create a column instance.
     */
    public function __construct(
        public string $index,
        public string $icon,
        public string $title,
        public string $message,
        public string $method,
        public mixed $action,
        public mixed $url,
        public mixed $confirmation,
    ) {
    }
}
