<?php

namespace App\Domain\Notifications\Push;

final class PushProviderFactory
{
    public static function make(): PushProviderInterface
    {
        $driver = config('noorly.push.driver', 'null');

        return match ($driver) {
            'fcm' => new FcmPushProvider(config('noorly.push.fcm.server_key')),
            default => new NullPushProvider,
        };
    }
}
