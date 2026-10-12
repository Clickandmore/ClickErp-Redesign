<?php

namespace App\Support;

class LoggingMailTransport implements \Swift_Transport
{
    public function __construct(private \Swift_Transport $transport) {}
    public function isStarted() { return $this->transport->isStarted(); }
    public function start() { return $this->transport->start(); }
    public function stop() { return $this->transport->stop(); }
    public function ping() { return $this->transport->ping(); }
    public function registerPlugin(\Swift_Events_EventListener $plugin) { $this->transport->registerPlugin($plugin); }

    public function send(\Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        $logs = [];
        $recipients = array_replace((array) $message->getTo(), (array) $message->getCc(), (array) $message->getBcc());
        foreach ($recipients as $email => $name) {
            $logs[$email] = OutboundMessageRecorder::begin('email', (string) $email,
                $message->getSubject() . "\n" . strip_tags((string) $message->getBody()), get_class($this->transport), ['recipient_name' => $name]);
        }
        try {
            $count = $this->transport->send($message, $failedRecipients);
            $simulated = $this->transport instanceof \Illuminate\Mail\Transport\ArrayTransport || $this->transport instanceof \Illuminate\Mail\Transport\LogTransport;
            foreach ($logs as $email => $log) {
                OutboundMessageRecorder::finish($log, $simulated ? 'skipped' : ($count > 0 && !in_array($email, (array) $failedRecipients, true) ? 'accepted' : 'failed'), [
                    'message_id' => $message->getId(), 'error_message' => $simulated ? 'قناة بريد تجريبية (array / log)؛ لم يرسل بريد خارجي.' : 'رفض خادم البريد المستلم.',
                ]);
            }
            return $count;
        } catch (\Throwable $e) {
            foreach ($logs as $log) OutboundMessageRecorder::finish($log, 'failed', ['error_message' => $e->getMessage()]);
            throw $e;
        }
    }
}
