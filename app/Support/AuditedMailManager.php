<?php

namespace App\Support;

class AuditedMailManager extends \Illuminate\Mail\MailManager
{
    protected function createSwiftMailer(array $config)
    {
        $swift = parent::createSwiftMailer($config);
        return new \Swift_Mailer(new LoggingMailTransport($swift->getTransport()));
    }
}
