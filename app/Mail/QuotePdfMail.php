<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class QuotePdfMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $quote
     */
    public function __construct(
        public array $quote,
        protected string $pdfContents,
        protected string $filename,
    ) {
        //
    }

    public function build(): self
    {
        return $this->subject(sprintf('Quote %s from Colorado Supply', $this->quote['number'] ?? ''))
            ->view('emails.quote')
            ->with([
                'quote' => $this->quote,
            ])
            ->attachData($this->pdfContents, $this->filename, [
                'mime' => 'application/pdf',
            ]);
    }
}
