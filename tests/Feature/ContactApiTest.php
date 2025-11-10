<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Contact;
use Tests\TestCase;

final class ContactApiTest extends TestCase
{
    public function test_can_list_all_contacts(): void
    {
        Contact::factory()->count(5)->create();

        $response = $this->getJson('/api/contacts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'phone', 'email', 'image_url'],
                ],
                'pagination' => ['total', 'per_page', 'current_page', 'last_page'],
            ]);
    }

    public function test_can_show_single_contact(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->getJson("/api/contacts/{$contact->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $contact->id)
            ->assertJsonPath('data.name', $contact->name)
            ->assertJsonPath('data.phone', $contact->phone);
    }

    public function test_can_create_contact(): void
    {
        $payload = [
            'name' => 'Jane Smith',
            'phone' => '9876543210',
            'email' => 'jane@example.com',
        ];

        $response = $this->postJson('/api/contacts', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Jane Smith')
            ->assertJsonPath('data.phone', '9876543210');

        $this->assertDatabaseHas('contacts', $payload);
    }

    public function test_cannot_create_contact_with_duplicate_phone(): void
    {
        $contact = Contact::factory()->create();

        $payload = [
            'name' => 'Another Person',
            'phone' => $contact->phone,
        ];

        $response = $this->postJson('/api/contacts', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('phone');
    }

    public function test_can_update_contact(): void
    {
        $contact = Contact::factory()->create();

        $payload = [
            'name' => 'Updated Name',
            'phone' => '5555555555',
            'email' => 'updated@example.com',
        ];

        $response = $this->putJson("/api/contacts/{$contact->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name');

        $this->assertDatabaseHas('contacts', $payload);
    }

    public function test_can_delete_contact(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->deleteJson("/api/contacts/{$contact->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }

    public function test_cannot_show_nonexistent_contact(): void
    {
        $response = $this->getJson('/api/contacts/99999');

        $response->assertStatus(404);
    }

    public function test_can_search_contacts_by_name(): void
    {
        Contact::factory()->create(['name' => 'John Doe']);
        Contact::factory()->create(['name' => 'Jane Smith']);

        $response = $this->getJson('/api/contacts?q=John');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_validation_name_required(): void
    {
        $payload = ['phone' => '1234567890'];

        $response = $this->postJson('/api/contacts', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_validation_phone_required(): void
    {
        $payload = ['name' => 'John'];

        $response = $this->postJson('/api/contacts', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('phone');
    }

    public function test_validation_email_format(): void
    {
        $payload = [
            'name' => 'John Doe',
            'phone' => '1234567890',
            'email' => 'invalid-email',
        ];

        $response = $this->postJson('/api/contacts', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }
}
