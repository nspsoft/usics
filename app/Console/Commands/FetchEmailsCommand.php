<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FetchEmailsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:fetch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch unread emails from IMAP and process with AI';

    /**
     * Execute the console command.
     */
    public function handle(\App\Services\EmailService $emailService)
    {
        $this->info('Starting email fetch...');
        
        try {
            $count = $emailService->fetchEmails();
            $this->info("Successfully processed {$count} new emails.");
        } catch (\Exception $e) {
            $this->error('Failed to fetch emails: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
