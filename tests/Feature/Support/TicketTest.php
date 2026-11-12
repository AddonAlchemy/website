<?php

declare(strict_types=1);

namespace Tests\Feature\Support;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_customers_open_tickets_for_the_platform_or_a_studio(): void
    {
        $customer = User::factory()->create();
        $admin = $this->admin();
        $vendor = $this->approvedVendor();

        $this->actingAs($customer)->post(route('support.store'), ['subject' => 'Frage', 'message' => 'Hallo Team'])->assertRedirect();
        $this->actingAs($customer)->post(route('support.store'), ['subject' => 'Bug', 'message' => 'Hallo Studio', 'vendor_id' => $vendor->id])->assertRedirect();

        $this->assertCount(1, $admin->notifications);
        $this->assertCount(1, $vendor->user->notifications);
        $this->assertMatchesRegularExpression('/^TCK(-[A-Z0-9]{4}){2}$/', Ticket::query()->firstOrFail()->number);
    }

    public function test_tickets_are_only_visible_to_participants(): void
    {
        $customer = User::factory()->create();
        $vendor = $this->approvedVendor();
        $this->actingAs($customer)->post(route('support.store'), ['subject' => 'Bug', 'message' => 'Hallo Studio', 'vendor_id' => $vendor->id]);
        $ticket = Ticket::query()->sole();

        $this->actingAs($customer)->get(route('support.show', $ticket))->assertOk()->assertSee('Hallo Studio');
        $this->actingAs($vendor->user)->get(route('support.show', $ticket))->assertOk();
        $this->actingAs($this->admin())->get(route('support.show', $ticket))->assertOk();
        $this->actingAs(User::factory()->create())->get(route('support.show', $ticket))->assertForbidden();
        $this->actingAs($this->approvedVendor()->user)->post(route('support.reply', $ticket), ['message' => 'Hi'])->assertForbidden();
    }

    public function test_replies_update_the_status_and_closed_tickets_are_locked(): void
    {
        $customer = User::factory()->create();
        $admin = $this->admin();
        $this->actingAs($customer)->post(route('support.store'), ['subject' => 'Frage', 'message' => 'Hallo Team']);
        $ticket = Ticket::query()->sole();

        $this->actingAs($admin)->post(route('support.reply', $ticket), ['message' => 'Antwort'])->assertSessionHas('success');
        $this->assertSame(TicketStatus::Answered, $ticket->refresh()->status);
        $this->assertCount(1, $customer->notifications);

        $this->actingAs($customer)->post(route('support.reply', $ticket), ['message' => 'Danke'])->assertSessionHas('success');
        $this->assertSame(TicketStatus::Open, $ticket->refresh()->status);

        $this->actingAs($customer)->patch(route('support.status', $ticket))->assertSessionHas('success');
        $this->actingAs($customer)->post(route('support.reply', $ticket), ['message' => 'Noch was'])->assertSessionHas('error');
        $this->assertSame(3, $ticket->messages()->count());

        $this->actingAs($customer)->patch(route('support.status', $ticket), ['reopen' => '1']);
        $this->assertSame(TicketStatus::Open, $ticket->refresh()->status);
    }
}
