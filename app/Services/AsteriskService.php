<?php

namespace App\Services;

use PAMI\Client\Impl\ClientImpl;
use PAMI\Message\Action\OriginateAction;
use PAMI\Message\Action\MonitorAction;
use PAMI\Message\Action\HangupAction;
use PAMI\Exception\PAMIException;
use Exception;

class AsteriskService
{
    protected ClientImpl $pamiClient;
    protected string $channel;
    protected string $recordingPath;

    public function __construct()
    {
        $this->loadConfig();
        $this->connectAsterisk();
    }

    /**
     * Load Asterisk configurations.
     */
    protected function loadConfig(): void
    {
        $this->channel = config('asterisk.channel');
        $this->recordingPath = config('asterisk.recordings_path');
    }

    /**
     * Establish a connection to the Asterisk server.
     */
    protected function connectAsterisk(): void
    {
        $options = [
            'host' => config('asterisk.host'),
            'port' => config('asterisk.port'),
            'username' => config('asterisk.username'),
            'secret' => config('asterisk.secret'),
            'connect_timeout' => 10,
            'read_timeout' => 10,
        ];

        try {
            $this->pamiClient = new ClientImpl($options);
            $this->pamiClient->open();
        } catch (PAMIException $e) {
            throw new Exception('Asterisk connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Play an audio message.
     */
    public function playAudio(string $audioFile): void
    {
        $originateAction = new OriginateAction($this->channel);
        $originateAction->setApplication('Playback');
        $originateAction->setData($audioFile);

        $this->pamiClient->send($originateAction);
    }

    /**
     * Handle digit input from the user.
     */
    public function handleDigitOption(): void
    {
        $this->playAudio('custom/enter-digit');

        $readAction = new OriginateAction($this->channel);
        $readAction->setApplication('Read');
        $readAction->setData('digit,,1');

        $this->pamiClient->send($readAction);

        $this->playAudio('custom/you-pressed');
    }

    /**
     * Record a message from the user.
     */
    public function recordMessage(): string
    {
        $uniqueId = uniqid();
        $recordingFilePath = $this->recordingPath . '/msg-' . $uniqueId . '.wav';

        $this->playAudio('custom/record-message');

        $monitorAction = new MonitorAction($this->channel, 'wav', $recordingFilePath);
        $this->pamiClient->send($monitorAction);

        $this->playAudio("recordings/msg-{$uniqueId}");

        return $recordingFilePath;
    }

    /**
     * Hang up the call.
     */
    public function hangUp(): void
    {
        $hangupAction = new HangupAction($this->channel);
        $this->pamiClient->send($hangupAction);
    }
}
