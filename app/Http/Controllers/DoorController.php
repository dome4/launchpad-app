<?php

namespace App\Http\Controllers;

use App\OpeningTime;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DoorController extends Controller
{
    /**
     * API Endpoint for the Arduino.
     * @param Request $request
     * @return array
     */
    public function changeStatusLegacy(Request $request)
    {
        $doorStatusWritten = $request->input('door');
        if (! $doorStatusWritten) {
            return ['error' => "Door parameter not set. Please use 'open' or 'closed' as values"];
        }

        $doorStatusWrittenToBool = [
            'open' => true,
            'closed' => false,
        ];

        if (! in_array($doorStatusWritten, array_keys($doorStatusWrittenToBool))) {
            return ['error' => "Unknown door value. Please use 'open' or 'closed'"];
        }

        $isOpen = $doorStatusWrittenToBool[$doorStatusWritten];
        $openingTime = $this->changeDoorStatus($isOpen);

        return $openingTime;
    }

    /**
     * Method to let a browser-based user open/close the door.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function changeStatus()
    {
        //get last opining time as variable
        $latest = $this->getLastOpeningTime();

        if ($latest->isOpen()) {
            $this->close();
            return redirect()->back();
        }

        $this->open();
        return redirect()->back();
    }

    /**
     * @param $toOpen
     * @return OpeningTime
     */
    public function changeDoorStatus($toOpen)
    {
        if ($toOpen) {
            return $this->open();
        }

        return $this->close();
    }

    /**
     * @return OpeningTime
     */
    public function open()
    {
        // create new opening (only when last one was closed)
        $openingTime = $this->getLastOpeningTime();
        $isLaunchpadClosed = $openingTime ? $openingTime->close_at : true;

        if ($isLaunchpadClosed) {
            $openingTime = OpeningTime::create([
                'open_at' => Carbon::now(),
                'close_at' => null,
                'is_public' => true,
                'is_visible' => true,
            ]);
        } else {
            // do nothing as it is already open
        }

        return $openingTime;
    }

    /**
     * @return mixed
     */
    public function close()
    {
        // get last opening time
        $openingTime = $this->getLastOpeningTime();
        $currentTime = Carbon::now();

        //compare last opening time with the current time plus 10 minutes
        if($openingTime->open_at->addMinutes(10) < $currentTime)
        {
            $openingTime->close_at = $currentTime;
            $openingTime->save();
        }

        return $openingTime;
    }

    /**
     * @return OpeningTime
     */
    public function getLastOpeningTime()
    {
        return OpeningTime::query()
            ->orderBy('open_at', 'DESC')
            ->first();
    }
}