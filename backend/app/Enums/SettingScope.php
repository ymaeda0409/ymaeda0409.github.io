<?php

namespace App\Enums;

/**
 * Levels a setting can be overridden at, most specific first.
 */
enum SettingScope: string
{
    case STORE = 'STORE';
    case FRANCHISE = 'FRANCHISE';
    case ORGANIZATION = 'ORGANIZATION';
    case GLOBAL = 'GLOBAL';
}
