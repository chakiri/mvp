<?php


namespace App\Events;


class LoggerEvent extends AbstractEvent
{
    const SERVICE_NEW  = 'service_new';
    const SERVICE_SHOW = 'service_show';
    const USER_NEW     = 'user_new';
    const USER_LOGIN    = 'user_login';
}