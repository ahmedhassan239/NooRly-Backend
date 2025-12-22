<?php

namespace App\Domain\Auth\Enums;

enum RegistrationMethod: string
{
    case Guest = 'guest';
    case Email = 'email';
    case Google = 'google';
    case Facebook = 'facebook';
    case Apple = 'apple';
}
