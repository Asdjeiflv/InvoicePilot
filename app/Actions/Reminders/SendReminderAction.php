<?php

namespace App\Actions\Reminders;

use App\Models\Invoice;
use App\Models\Reminder;
use Illuminate\Support\Facades\Mail;

class SendReminderAction
{
    private const TEMPLATES = [
        'soft' => [
            'subject' => 'Friendly Payment Reminder - Invoice {invoice_no}',
            'body' => 'Dear {client_name}, this is a friendly reminder that invoice {invoice_no} for {total} is due on {due_date}. Balance due: {balance_due}.',
        ],
        'normal' => [
            'subject' => 'Payment Reminder - Invoice {invoice_no}',
            'body' => 'Dear {client_name}, invoice {invoice_no} for {total} was due on {due_date}. Please arrange payment as soon as possible. Balance due: {balance_due}.',
        ],
        'final' => [
            'subject' => 'FINAL NOTICE - Invoice {invoice_no}',
            'body' => 'Dear {client_name}, this is a FINAL NOTICE for invoice {invoice_no}. The payment of {balance_due} is now overdue. Immediate action is required.',
        ],
    ];

    public function execute(Invoice $invoice, string $type = 'normal'): Reminder
    {
        if (!$invoice->canSendReminder()) {
            throw new \RuntimeException('Reminder cannot be sent for this invoice');
        }

        if (!in_array($type, ['soft', 'normal', 'final'])) {
            throw new \InvalidArgumentException("Invalid reminder type: {type}");
        }

        // Check for recent reminder (within 7 days)
        $recentReminder = $invoice->reminders()
            ->where('sent_at', '>=', now()->subDays(7))
            ->latest('sent_at')
            ->first();

        if ($recentReminder) {
            throw new \RuntimeException(
                sprintf(
                    'A reminder was already sent on %s. Please wait at least 7 days before sending another reminder.',
                    $recentReminder->sent_at->format('Y-m-d H:i:s')
                )
            );
        }

        $template = self::TEMPLATES[$type];
        $subject = $this->replaceVariables($template['subject'], $invoice);
        $body = $this->replaceVariables($template['body'], $invoice);

        // Create reminder record
        $reminder = Reminder::create([
            'invoice_id' => $invoice->id,
            'reminder_type' => $type,
            'sent_to' => $invoice->client->email,
            'subject' => $subject,
            'body' => $body,
            'sent_at' => now(),
            'sent_by' => auth()->id(),
        ]);

        // Send email (using log driver for now, can be configured to use real mailer)
        Mail::raw($body, function ($message) use ($invoice, $subject) {
            $message->to($invoice->client->email)
                    ->subject($subject);
        });

        return $reminder;
    }

    private function replaceVariables(string $template, Invoice $invoice): string
    {
        return str_replace(
            ['{invoice_no}', '{client_name}', '{total}', '{due_date}', '{balance_due}'],
            [
                $invoice->invoice_no,
                $invoice->client->company_name,
                number_format($invoice->total, 2),
                $invoice->due_date->format('Y-m-d'),
                number_format($invoice->balance_due, 2),
            ],
            $template
        );
    }
}
