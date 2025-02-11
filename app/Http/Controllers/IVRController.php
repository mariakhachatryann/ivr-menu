<?php

namespace App\Http\Controllers;

use App\Services\AsteriskService;
use Illuminate\Http\Request;

class IVRController extends Controller
{
    protected $asteriskService;
    private const OPTION_SMILE = "1";
    private const OPTION_DIGIT = "2";
    private const OPTION_RECORD = "3";

    public function __construct(AsteriskService $asteriskService)
    {
        $this->asteriskService = $asteriskService;
    }

    /**
     * Handle the incoming IVR request and process the digit input.
     */
    public function handleIVR(Request $request)
    {
        $digit = $request->input('digit');

        switch ($digit) {
            case self::OPTION_SMILE:
                $this->asteriskService->playAudio('custom/smile'); // "You smiled"
                break;
            case self::OPTION_DIGIT:
                $this->asteriskService->handleDigitOption(); // Handle digit input
                break;
            case self::OPTION_RECORD:
                $file = $this->asteriskService->recordMessage(); // Handle voice message recording
                return response()->json(['message' => 'Recording started', 'file' => $file]);
            default:
                $this->asteriskService->playAudio('custom/invalid-option'); // Invalid option
        }

        $this->asteriskService->hangUp();
        return response()->json(['message' => 'IVR action processed']);
    }
}
