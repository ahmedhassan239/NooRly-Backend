<?php

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Contracts\SocialAuthProvider;
use App\Domain\Auth\Enums\Provider;
use Illuminate\Contracts\Container\Container;

class SocialAuthProviderFactory
{
    public function __construct(
        private readonly Container $container
    ) {}

    public function make(Provider $provider): SocialAuthProvider
    {
        return match ($provider) {
            Provider::Google => $this->container->make(GoogleAuthProvider::class),
            Provider::Facebook => $this->container->make(FacebookAuthProvider::class),
            Provider::Apple => $this->container->make(AppleAuthProvider::class),
        };
    }
}
