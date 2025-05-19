<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
// use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProcessMail extends Mailable
{
  use Queueable, SerializesModels;

  public $data;
  public $body;

  /**
   * Create a new message instance.
   *
   * @return void
   */
  public function __construct($data)
  {
    $this->data = $data;
    $this->body = $data['body'];
  }

  /**
   * Build the message.
   *
   *->bcc(env('MAIL_FROM_ADDRESS'), "TrustExplorer Support")
   * @return $this
   */
  public function build()
  {
    $subject = $this->data['subject'];
    return $this->view($this->data['view'])
        ->from(env('MAIL_FROM_ADDRESS'), "TrustExplorer Support")
        ->subject($subject)->with(['body' => $this->body]);
  }
}
