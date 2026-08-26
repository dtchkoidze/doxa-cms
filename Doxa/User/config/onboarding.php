<?php

return [
    /**
     * Query → row spec. Empty = onboarding off (cookie flow unchanged).
     * Host example:
     * 'referer' => ['target' => 'success_url'],
     * Optional: 'type' => 'next_step' — written only when set.
     */
    'onboarding_query_keys' => [],

    /**
     * Host subclass of Doxa\User\Libraries\Onboarding, or null for this class.
     */
    'handler' => null,
];
