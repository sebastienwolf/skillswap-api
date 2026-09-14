<?php

namespace Tests\Feature\Reservations;

use App\Enums\ExchangeStatus;
use App\Enums\ExchangeType;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReservationFlowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_member_can_request_a_reservation_on_someone_elses_item(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $requester = User::factory()->create();
        $item = Item::factory()->for($owner)->create(['type' => ExchangeType::Offer, 'status' => ExchangeStatus::Published]);

        $response = $this->actingAs($requester, 'sanctum')->postJson('/api/reservations', [
            'reservable_type' => 'item',
            'reservable_id' => $item->id,
            'message' => 'Toujours dispo ?',
        ]);

        $response->assertCreated()->assertJsonPath('data.status', 'pending');
        $this->assertDatabaseHas('reservations', ['reservable_id' => $item->id, 'requester_id' => $requester->id]);
    }

    #[Test]
    public function a_member_cannot_reserve_their_own_item(): void
    {
        $owner = User::factory()->create();
        $item = Item::factory()->for($owner)->create();

        $this->actingAs($owner, 'sanctum')->postJson('/api/reservations', [
            'reservable_type' => 'item',
            'reservable_id' => $item->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('reservable');
    }

    #[Test]
    public function accepting_a_reservation_locks_the_item_and_declines_other_requests(): void
    {
        $owner = User::factory()->create();
        $item = Item::factory()->for($owner)->create(['status' => ExchangeStatus::Published]);
        $firstRequester = User::factory()->create();
        $secondRequester = User::factory()->create();

        $first = $item->reservations()->create(['requester_id' => $firstRequester->id]);
        $second = $item->reservations()->create(['requester_id' => $secondRequester->id]);

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/reservations/{$first->id}/accept")
            ->assertOk()
            ->assertJsonPath('data.status', 'accepted');

        $this->assertSame(ExchangeStatus::Reserved, $item->fresh()->status);
        $this->assertSame('declined', $second->fresh()->status->value);
    }

    #[Test]
    public function only_the_resource_owner_can_accept_a_reservation(): void
    {
        $owner = User::factory()->create();
        $requester = User::factory()->create();
        $item = Item::factory()->for($owner)->create();
        $reservation = $item->reservations()->create(['requester_id' => $requester->id]);

        $this->actingAs($requester, 'sanctum')
            ->postJson("/api/reservations/{$reservation->id}/accept")
            ->assertForbidden();
    }

    #[Test]
    public function completing_a_reservation_marks_the_item_as_completed(): void
    {
        $owner = User::factory()->create();
        $requester = User::factory()->create();
        $item = Item::factory()->for($owner)->create(['status' => ExchangeStatus::Reserved]);
        $reservation = $item->reservations()->create([
            'requester_id' => $requester->id,
            'status' => \App\Enums\ReservationStatus::Accepted,
        ]);

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/reservations/{$reservation->id}/complete")
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $this->assertSame(ExchangeStatus::Completed, $item->fresh()->status);
    }

    #[Test]
    public function cancelling_an_accepted_reservation_frees_the_item_again(): void
    {
        $owner = User::factory()->create();
        $requester = User::factory()->create();
        $item = Item::factory()->for($owner)->create(['status' => ExchangeStatus::Reserved]);
        $reservation = $item->reservations()->create([
            'requester_id' => $requester->id,
            'status' => \App\Enums\ReservationStatus::Accepted,
        ]);

        $this->actingAs($requester, 'sanctum')
            ->postJson("/api/reservations/{$reservation->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertSame(ExchangeStatus::Published, $item->fresh()->status);
    }
}
