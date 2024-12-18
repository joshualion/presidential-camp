<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendSmsBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $recipients;
    protected $message;
    protected $apiToken;
    protected $senderId;

    /**
     * Create a new job instance.
     */
    public function __construct($recipients, $message, $apiToken, $senderId)
    {
        $this->recipients = $recipients;
        $this->message = $message;
        $this->apiToken = $apiToken;
        $this->senderId = $senderId;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $url = 'https://wiredirectsms.com/api/v3/sms/send';

        $data = [
            'recipient' => implode(', ', $this->recipients),
            'sender_id' => $this->senderId,
            'type' => 'plain',
            'message' => $this->message,
        ];

        $headers = [
            'Accept: application/json',
            'Authorization: Bearer ' . $this->apiToken,
            'Content-Type: application/json',
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        curl_close($ch);

        // Handle response or logging here
    }
}
