<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\ContactDTO;
use App\Models\Contact;
use App\Repositories\ContactRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ContactService
{
    public function __construct(private ContactRepository $repository) {}

    public function getAll(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->all($perPage);
    }

    public function search(string $query, int $perPage = 15): LengthAwarePaginator
    {
        if (empty($query)) {
            return $this->getAll($perPage);
        }

        return $this->repository->search($query, $perPage);
    }

    public function getById(int $id): ?Contact
    {
        return $this->repository->findById($id);
    }

    public function create(ContactDTO $dto): Contact
    {
        return $this->repository->create($dto->toArray());
    }

    public function update(int $id, ContactDTO $dto): Contact
    {
        $contact = $this->repository->findById($id);

        if (!$contact) {
            throw new \Exception('Contact not found', 404);
        }

        $this->repository->update($contact, $dto->toArray());

        return $contact->fresh();
    }

    public function delete(int $id): bool
    {
        $contact = $this->repository->findById($id);

        if (!$contact) {
            throw new \Exception('Contact not found', 404);
        }

        return $this->repository->delete($contact);
    }

    public function phoneExists(string $phone, ?int $excludeId = null): bool
    {
        $contact = $this->repository->findByPhone($phone);

        return $contact && (!$excludeId || $contact->id !== $excludeId);
    }
}
