<?php

namespace App\Domain\Auth\Enums;

enum UserStatus: string
{
    case Active = 'active';
    case Disabled = 'disabled';
    case Banned = 'banned';
}
