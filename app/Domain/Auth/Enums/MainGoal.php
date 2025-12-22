<?php

namespace App\Domain\Auth\Enums;

enum MainGoal: string
{
    case Salah = 'salah';
    case QuranBasics = 'quran_basics';
    case FaithEssentials = 'faith_essentials';
    case Exploring = 'exploring';
}
