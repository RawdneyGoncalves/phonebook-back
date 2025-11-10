<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTOs\ContactDTO;
use App\Models\Contact;
use App\Repositories\ContactRepository;
use App\Services\ContactService;
use PHPUnit\Framework\TestCase;

final class ContactServiceTest extends TestCase
{
    private ContactService $service;

    /** @var ContactRepository&\PHPUnit\Framework\MockObject\MockObject */
    private ContactRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(ContactRepository::class);
        $this->service = new ContactService($this->repository);
    }

    public function test_phone_exists_returns_false_when_not_found(): void
    {
        $this->repository
            ->method('findByPhone')
            ->with('1234567890')
            ->willReturn(null);

        $exists = $this->service->phoneExists('1234567890');
        $this->assertFalse($exists);
    }

    public function test_phone_exists_returns_true_when_found(): void
    {
        $contact = new Contact();
        $contact->id = 1;

        $this->repository
            ->method('findByPhone')
            ->with('1234567890')
            ->willReturn($contact);

        $exists = $this->service->phoneExists('1234567890');
        $this->assertTrue($exists);
    }

    public function test_create_contact_with_dto(): void
    {
        $dto = new ContactDTO(
            name: 'joao silva',
            phone: '1234567890',
            email: 'joaosilva@example.com'
        );

        $contact = new Contact();
        $contact->id = 1;
        $contact->name = $dto->name;
        $contact->phone = $dto->phone;
        $contact->email = $dto->email;

        $this->repository
            ->method('create')
            ->with($dto)
            ->willReturn($contact);

        $result = $this->service->create($dto);

        $this->assertNotNull($result);
        $this->assertEquals($dto->name, $result->name);
        $this->assertEquals($dto->phone, $result->phone);
        $this->assertEquals($dto->email, $result->email);
    }
}
