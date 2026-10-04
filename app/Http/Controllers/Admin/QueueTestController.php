<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendQueueTestEmail;
use Illuminate\Http\RedirectResponse;

class QueueTestController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        $connection = 'database';

        SendQueueTestEmail::dispatch()
            ->onConnection($connection)
            ->onQueue('default');

        return back()->with(
            'queue_test_success',
            'Test email job queued for jacob.atam@gmail.com using the '.$connection.' queue connection.'
        );
    }
}
