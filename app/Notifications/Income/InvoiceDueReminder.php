<?php

namespace App\Notifications\Income;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Date;

class InvoiceDueReminder extends Notification
{
    /**
     * The bill model.
     *
     * @var object
     */
    public $invoice;

    /**
     * Create a notification instance.
     *
     * @param  object  $invoice
     */
    public function __construct($invoice, $afterDueDate)
    {
        $this->queue = 'high';
        $this->delay = config('queue.connections.database.delay');

        $this->invoice = $invoice;
        $this->afterDueDate = $afterDueDate;
    }

    /**
     * Get the notification's channels.
     *
     * @param  mixed  $notifiable
     * @return array|string
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $message = (new MailMessage)->line(trans('invoices.notification.due_reminder_message', 
                         [ 'amount' => money($this->invoice->amount, $this->invoice->currency_code, true),
                           'customer' => $this->invoice->customer_name,
                           'invoice_number' => $this->invoice->invoice_number,
                           'dueDays' => $this->afterDueDate]));

        // Override per company as Laravel doesn't read config
        $message->from(config('mail.from.address'), config('mail.from.name'));

        // Days between invoiced and due date
        $diff_days = Date::parse($this->invoice->due_at)->diffInDays(Date::today());

        $message->subject("Upozornenie na neuhradenú faktúru: ".
                            $this->invoice->invoice_number.", ".$this->afterDueDate." dni po splatnosti.");
                            
        $message->line(trans('invoices.notification.warning_message'));
        if($this->invoice->customer->email_cc && strlen($this->invoice->customer->email_cc) > 5 ){
                $message->cc($this->invoice->customer->email_cc);
        }
                    

        // Attach the PDF file if available
        if (isset($this->invoice->pdf_path)) {
            $message->attach($this->invoice->pdf_path, [
                'mime' => 'application/pdf',
            ]);
        }

        if ($this->invoice->customer->user) {
            $message->action(trans('invoices.notification.button'), url('customers/invoices', $this->invoice->id));
        }

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'invoice_id' => $this->invoice->id,
            'amount' => $this->invoice->amount,
        ];
    }
}
