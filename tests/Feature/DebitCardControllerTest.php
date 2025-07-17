<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DebitCardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Passport::actingAs($this->user);
    }

    public function testCustomerCanSeeAListOfDebitCards()
    {
        // get /debit-cards

        $debitCardOne = DebitCard::factory()->create([
            'user_id' => $this->user->id,
            'number' => 1234567890123456,
            'type' => 'Visa',
        ]);

        $debitCardTwo = DebitCard::factory()->create([
            'user_id' => $this->user->id,
            'number' => 6543210987654321,
            'type' => 'Mastercard',
        ]);

        // Make a GET request to the debit cards endpoint
        $response = $this->getJson('/api/debit-cards');

        // Assert the response is OK and contains only the authenticated user's debit cards
        $response->assertStatus(200)
            ->assertJsonFragment(['number' => 1234567890123456])
            ->assertJsonFragment(['number' => 6543210987654321]);
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        // get /debit-cards

        // Create another user and their debit card
        $otherUser = User::factory()->create();
        $otherDebitCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id,
            'number' => 1111222233334444,
            'type' => 'Visa',
        ]);

        // Make a GET request as the authenticated user
        $response = $this->getJson('/api/debit-cards');

        // Assert the response does NOT contain the other user's debit card
        $response->assertStatus(200)
            ->assertJsonMissing(['number' => 1111222233334444]);
    }

    public function testCustomerCanCreateADebitCard()
    {
        // post /debit-cards
        
        $payload = [
            'type' => 'Visa',
        ];

        $response = $this->postJson('/api/debit-cards', $payload);

        $response->assertStatus(201)
            ->assertJsonFragment(['type' => 'Visa']);

        // Check the card exists in the database for the user
        $this->assertDatabaseHas('debit_cards', [
            'user_id' => $this->user->id,
            'type' => 'Visa',
        ]);
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}

        // Create a debit card for the authenticated user
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id,
            'number' => 1234567890123456,
            'type' => 'Visa',
        ]);

        // Make a GET request to the single debit card endpoint
        $response = $this->getJson("/api/debit-cards/{$debitCard->id}");

        // Assert the response is OK and contains the correct debit card details
        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $debitCard->id,
                'number' => 1234567890123456,
                'type' => 'Visa',
            ]);
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}

        // Assuming that this means that the customer 
        // shouldn't be able to access cards that don't belong to them

        // Create another user and their debit card
        $otherUser = User::factory()->create();
        
        $otherDebitCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id,
            'number' => 1111222233334444,
            'type' => 'Visa',
        ]);

        // Try to access the other user's debit card as the authenticated user
        $response = $this->getJson("/api/debit-cards/{$otherDebitCard->id}");

        // Assert forbidden or not found (depending on your policy/controller)
        $response->assertStatus(403);
    }

    public function testCustomerCanActivateADebitCard()
    {
        // put api/debit-cards/{debitCard}

        // Create a debit card for the authenticated user
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id,
            'number' => 1234567890123456,
            'type' => 'Visa',
        ]);

        $response = $this->putJson("/api/debit-cards/{$debitCard->id}",
        [
            'is_active' => true,
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $debitCard->id,
                'number' => 1234567890123456,
                'type' => 'Visa',
                'is_active' => true
            ]);
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        // put api/debit-cards/{debitCard}

        // Create a debit card for the authenticated user
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id,
            'number' => 1234567890123456,
            'type' => 'Visa',
        ]);

        $response = $this->putJson("/api/debit-cards/{$debitCard->id}",
        [
            'is_active' => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $debitCard->id,
                'number' => 1234567890123456,
                'type' => 'Visa',
                'is_active' => false
            ]);
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        // put api/debit-cards/{debitCard}

        // create a debit card with another user
        $otherUser = User::factory()->create();
        
        $otherDebitCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id,
            'number' => 1111222233334444,
            'type' => 'Visa',
        ]);

        $response = $this->putJson("/api/debit-cards/{$otherDebitCard->id}",
        [
            'is_active' => true,
        ]);

        $response->assertStatus(403);
    }

    public function testCustomerCanDeleteADebitCard()
    {
        // delete api/debit-cards/{debitCard}

        // Create debit cards for the user
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id,
            'number' => 1234567890123457,
            'type' => 'Visa',
        ]);

        $response = $this->deleteJson("/api/debit-cards/{$debitCard->id}");

        $response->assertStatus(204);

    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        // delete api/debit-cards/{debitCard}

        // Create debit cards for the user
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id,
            'number' => 1234567890123457,
            'type' => 'Visa',
        ]);

        // Create some transactions for a debit card
        $transactionOne = DebitCardTransaction::factory()->create([
            'debit_card_id' => $debitCard->id,
            'amount' => 500,
            'currency_code' => DebitCardTransaction::CURRENCY_VND,
        ]);

        $transactionTwo = DebitCardTransaction::factory()->create([
            'debit_card_id' => $debitCard->id,
            'amount' => 450,
            'currency_code' => DebitCardTransaction::CURRENCY_VND,
        ]);

        $response = $this->deleteJson("/api/debit-cards/{$debitCard->id}");

        $response->assertStatus(403);
    }

    // Extra bonus for extra tests :)

}
