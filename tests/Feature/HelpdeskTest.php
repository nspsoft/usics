<?php

namespace Tests\Feature;

use App\Models\HelpdeskTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpdeskTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_helpdesk_dashboard()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('helpdesk.index'));

        $response->assertStatus(200);
    }

    public function test_user_can_create_helpdesk_ticket()
    {
        $user = User::factory()->create();

        $ticketData = [
            'title' => 'Test Bug Ticket',
            'category' => 'bug',
            'priority' => 'high',
            'description' => 'Detailed description of the bug encountered on sales page.',
            'url' => 'https://erp.test/sales/orders/123',
        ];

        $response = $this->actingAs($user)->post(route('helpdesk.store'), $ticketData);

        $ticket = HelpdeskTicket::where('title', 'Test Bug Ticket')->first();

        $this->assertNotNull($ticket);
        $this->assertEquals('bug', $ticket->category);
        $this->assertEquals('high', $ticket->priority);
        $this->assertEquals('open', $ticket->status);

        $response->assertRedirect(route('helpdesk.show', $ticket->id));
    }

    public function test_user_can_reply_to_ticket_and_update_status()
    {
        $user = User::factory()->create();
        $admin = User::factory()->create();

        $ticket = HelpdeskTicket::create([
            'ticket_number' => 'TKT-202606-0001',
            'user_id' => $user->id,
            'title' => 'Sample Ticket',
            'category' => 'revision',
            'priority' => 'medium',
            'status' => 'open',
            'description' => 'Initial ticket description',
        ]);

        // Reply to ticket
        $replyResponse = $this->actingAs($admin)->post(route('helpdesk.reply', $ticket->id), [
            'message' => 'We are working on this revision now.',
        ]);
        $replyResponse->assertSessionHasNoErrors();

        $this->assertCount(1, $ticket->fresh()->replies);

        // Update status
        $statusResponse = $this->actingAs($admin)->put(route('helpdesk.status', $ticket->id), [
            'status' => 'resolved',
            'assigned_to' => $admin->id,
        ]);
        $statusResponse->assertSessionHasNoErrors();

        $ticket->refresh();
        $this->assertEquals('resolved', $ticket->status);
        $this->assertEquals($admin->id, $ticket->assigned_to);
    }
}
