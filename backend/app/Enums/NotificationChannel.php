<?php

namespace App\Enums;

enum NotificationChannel: string
{
    case PUSH = 'PUSH';
    case SMS = 'SMS';
    case EMAIL = 'EMAIL';
}
