<?php

namespace App\Domain\Auth\Enums;

enum Provider: string
{
    case Google = 'google';
    case Facebook = 'facebook';
    case Apple = 'apple';
}
