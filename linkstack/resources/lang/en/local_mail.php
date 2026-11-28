<?php
return [
    'enable' => 'Use local PHP mail',
    'description' => 'Requires a working local mail service and a sender at your own domain. Local acceptance does not confirm delivery.',
    'domain' => 'Sender domain',
    'hourly_limit' => 'Maximum messages per hour',
    'envelope' => 'Envelope sender mode',
    'envelope_from' => 'Use the configured sender (-f)',
    'envelope_provider' => 'Let the hosting provider choose (alignment must be verified)',
    'domain_error' => 'The sender address must belong to the configured domain.',
    'accepted' => 'The message was accepted locally. Check the recipient inbox and spam folder; delivery is not guaranteed.',
    'log_only' => 'The message was logged only. No email was sent.',
];
