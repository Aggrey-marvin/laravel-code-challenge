<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DebitCardTransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected DebitCard $debitCard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id
        ]);
        Passport::actingAs($this->user);
    }

    public function testCustomerCanSeeAListOfDebitCardTransactions()
    {
        // get /debit-card-transactions

        // create the transactions
        $transactionOne = DebitCardTransaction::factory()->create([
            'debit_card_id' => $this->debitCard->id,
            'amount' => 500,
            'currency_code' => DebitCardTransaction::CURRENCY_VND,
        ]);

        $transactionTwo = DebitCardTransaction::factory()->create([
            'debit_card_id' => $this->debitCard->id,
            'amount' => 450,
            'currency_code' => DebitCardTransaction::CURRENCY_VND,
        ]);

        // call the endpoint
        $response = $this->getJson("/api/debit-card-transactions", [
            'debit_card_id' => $this->debitCard->id
        ]);

        // assert that the created transactions are returned
        $response->assertStatus(200);

        $response->assertJsonFragment([
            'id' => $transactionOne->id,
            'amount' => $transactionOne->amount,
            'debit_card_id' => $this->debitCard->id,
            'currency_code' => $transactionOne->currency_code
        ]);

        $response->assertJsonFragment([
            'id' => $transactionTwo->id,
            'amount' => $transactionTwo->amount,
            'debit_card_id' => $this->debitCard->id,
            'currency_code' => $transactionTwo->currency_code
        ]);
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        // get /debit-card-transactions
        $otherUser = User::factory()->create();

        $otherDebitCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id,
            'number' => 1111222233334444,
            'type' => 'Visa',
        ]);

        $transactionOne = DebitCardTransaction::factory()->create([
            'debit_card_id' => $otherDebitCard,
            'amount' => 500,
            'currency_code' => DebitCardTransaction::CURRENCY_VND,
        ]);

        $transactionTwo = DebitCardTransaction::factory()->create([
            'debit_card_id' => $otherDebitCard,
            'amount' => 450,
            'currency_code' => DebitCardTransaction::CURRENCY_VND,
        ]);

        $response = $this->getJson("/api/debit-card-transactions", [
            'debit_card_id' => $otherDebitCard->id
        ]);

        $response->assertStatus(403);

        $response->assertJsonMissing([
            'id' => $transactionOne->id,
            'amount' => $transactionOne->amount,
            'debit_card_id' => $otherDebitCard->id,
            'currency_code' => $transactionOne->currency_code
        ]);

        $response->assertJsonMissing([
            'id' => $transactionTwo->id,
            'amount' => $transactionTwo->amount,
            'debit_card_id' => $otherDebitCard->id,
            'currency_code' => $transactionTwo->currency_code
        ]);

    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        // post /debit-card-transactions

        $response = $this->postJson("/api/debit-card-transactions", [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 500,
            'currency_code' => DebitCardTransaction::CURRENCY_VND
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'amount' => 500,
                'currency_code' => DebitCardTransaction::CURRENCY_VND
            ]);

        $this->assertDatabaseHas('debit_card_transactions', [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 500,
            'currency_code' => DebitCardTransaction::CURRENCY_VND
        ]);
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        // post /debit-card-transactions

        $otherUser = User::factory()->create();

        $otherDebitCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id,
            'number' => 1111222233334444,
            'type' => 'Visa',
        ]);

        $response = $this->postJson("/api/debit-card-transactions", [
            'debit_card_id' => $otherDebitCard->id,
            'amount' => 500,
            'currency_code' => DebitCardTransaction::CURRENCY_VND
        ]);

        $response->assertStatus(403);
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        // get /debit-card-transactions/{debitCardTransaction}

        $transactionOne = DebitCardTransaction::factory()->create([
            'debit_card_id' => $this->debitCard->id,
            'amount' => 500,
            'currency_code' => DebitCardTransaction::CURRENCY_VND,
        ]);

        $response = $this->getJson("/api/debit-card-transactions/{$transactionOne->id}");

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'amount' => $transactionOne->amount,
            'currency_code' => $transactionOne->currency_code
        ]);
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        // get /debit-card-transactions/{debitCardTransaction}

        $otherUser = User::factory()->create();

        $otherDebitCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id,
            'number' => 1111222233334444,
            'type' => 'Visa',
        ]);

         $transactionOne = DebitCardTransaction::factory()->create([
            'debit_card_id' => $otherDebitCard->id,
            'amount' => 500,
            'currency_code' => DebitCardTransaction::CURRENCY_VND,
        ]);

        $response = $this->getJson("/api/debit-card-transactions/{$transactionOne->id}");

        $response->assertStatus(403);
    }

    // Extra bonus for extra tests :)
}
