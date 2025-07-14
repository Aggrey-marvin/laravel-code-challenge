<?php

namespace Tests\Feature;

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

    // public function testCustomerCanSeeAListOfDebitCards()
    // {
    //     // get /debit-cards
    // }

    // public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    // {
    //     // get /debit-cards
    // }

    public function testCustomerCanCreateADebitCard()
    {
        // post /debit-cards
        $payload = [
            'number' => '1234567890123456',
            'type' => 'Visa',
            'expiration_date' => now()->addYear()->format('Y-m-d'),
        ];

        $response = $this->postJson('/api/debit-cards', $payload);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'number' => '1234567890123456',
                'type' => 'Visa',
            ]);

        $this->assertDatabaseHas('debit_cards', [
            'number' => '1234567890123456',
            'type' => 'Visa',
            'user_id' => $this->user->id,
        ]);
    }

    // public function testCustomerCanSeeASingleDebitCardDetails()
    // {
    //     // get api/debit-cards/{debitCard}
    // }

    // public function testCustomerCannotSeeASingleDebitCardDetails()
    // {
    //     // get api/debit-cards/{debitCard}
    // }

    // public function testCustomerCanActivateADebitCard()
    // {
    //     // put api/debit-cards/{debitCard}
    // }

    // public function testCustomerCanDeactivateADebitCard()
    // {
    //     // put api/debit-cards/{debitCard}
    // }

    // public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    // {
    //     // put api/debit-cards/{debitCard}
    // }

    // public function testCustomerCanDeleteADebitCard()
    // {
    //     // delete api/debit-cards/{debitCard}
    // }

    // public function testCustomerCannotDeleteADebitCardWithTransaction()
    // {
    //     // delete api/debit-cards/{debitCard}
    // }

    // Extra bonus for extra tests :)
}
