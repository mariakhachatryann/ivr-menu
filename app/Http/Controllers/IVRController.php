<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PAMI\Client\Impl\ClientImpl;
use PAMI\Message\Action\OriginateAction;
use PAMI\Message\Action\MonitorAction;
use PAMI\Message\Action\HangupAction;

class IVRController extends Controller
{
    /**
     * Establish connection to the Asterisk server.
     *
     * @return \PAMI\Client\Impl\ClientImpl
     */
    protected function connectAsterisk()
    {
        $options = [
            'host' => env('ASTERISK_HOST', '127.0.0.1'),
            'port' => env('ASTERISK_PORT', 5038),
            'username' => env('ASTERISK_USER', 'laravel'),
            'secret' => env('ASTERISK_SECRET', 'your_password'),
            'connect_timeout' => 10,
            'read_timeout' => 10,
        ];

        return new ClientImpl($options);
    }

    /**
     * Handle the incoming IVR request and process the digit input.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleIVR(Request $request)
    {
        $digit = $request->input('digit');

        $pamiClient = $this->connectAsterisk();
        $pamiClient->open();

        switch ($digit) {
            case "1":
                $this->playAudio($pamiClient, "custom/smile"); // "Вы улыбнулись"
                break;

            case "2":
                return $this->handleDigitOption($pamiClient); // Handle digit input

            case "3":
                return $this->recordMessage($pamiClient); // Handle voice message recording

            default:
                $this->playAudio($pamiClient, "custom/invalid-option");  // Invalid option
        }

        $pamiClient->close();

        return response()->json(['message' => 'IVR action processed']);
    }

    /**
     * Play a predefined audio message to the user.
     *
     * @param \PAMI\Client\Impl\ClientImpl $pamiClient
     * @param string $message
     * @return void
     */
    protected function playAudio($pamiClient, $message)
    {
        $channel = env('ASTERISK_CHANNEL');

        $originateAction = new OriginateAction($channel);
        $originateAction->setApplication("Playback");
        $originateAction->setData($message);
        $pamiClient->send($originateAction);
    }

    /**
     * Handle the input of a digit from the user.
     *
     * @param \PAMI\Client\Impl\ClientImpl $pamiClient
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleDigitOption($pamiClient)
    {
        $channel = env('ASTERISK_CHANNEL');

        $this->playAudio($pamiClient, "custom/enter-digit");

        $readAction = new OriginateAction($channel);
        $readAction->setApplication("Read");
        $readAction->setData("digit,,1");

        $pamiClient->send($readAction);
        $this->playAudio($pamiClient, "custom/you-pressed");

        return response()->json(['message' => 'Digit option processed']);
    }


    /**
     * Record a message from the user.
     *
     * @param \PAMI\Client\Impl\ClientImpl $pamiClient
     * @return \Illuminate\Http\JsonResponse
     */
    protected function recordMessage($pamiClient)
    {
        $channel = env('ASTERISK_CHANNEL');

        $this->playAudio($pamiClient, "custom/record-message");

        $uniqueId = uniqid();
        $recordingFilePath = "/var/spool/asterisk/recordings/msg-{$uniqueId}.wav";

        $monitorAction = new MonitorAction($channel, 'wav', $recordingFilePath);
        $pamiClient->send($monitorAction);

        $this->playAudio($pamiClient, "custom/recording-complete");
        $this->playAudio($pamiClient, "recordings/msg-{$uniqueId}");
        return response()->json(['message' => 'Recording started', 'file' => $recordingFilePath]);
    }


    /**
     * Hang up the call after the IVR process.
     *
     * @param \PAMI\Client\Impl\ClientImpl $pamiClient
     * @return void
     */
    protected function hangUp($pamiClient)
    {
        $channel = env('ASTERISK_CHANNEL');

        $hangupAction = new HangupAction($channel);
        $pamiClient->send($hangupAction);
    }
}
