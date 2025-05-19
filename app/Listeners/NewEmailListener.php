<?php

namespace App\Listeners;

use App\Events\SendNewMail;
use App\Mail\ProcessMail;
use Illuminate\Support\Facades\Mail;
// use Illuminate\Contracts\Queue\ShouldQueue;
// use Illuminate\Queue\InteractsWithQueue;

class NewEmailListener
{
  /**
   * Create the event listener.
   *
   * @return void
   */
  public function __construct()
  {
    //
  }

  /**
   * Handle the event.
   *
   * @param  SendNewMail  $event
   * @return void
   */
  public function handle(SendNewMail $event)
  {
    // dd($event->email_data);
    $data = $event->email_data;
    Mail::to($data['to'])->send(new ProcessMail($data)); 
  }
}