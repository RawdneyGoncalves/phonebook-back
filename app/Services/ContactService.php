<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\ContactDTO;
use App\Models\Contact;
use App\Repositories\ContactRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

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
        $contact = $this->repository->findById($id);

        if ($contact && $contact->user_id !== Auth::id()) {
            return null;
        }

        return $contact;
    }

    public function create(ContactDTO $dto): Contact
    {
        $data = $dto->toArray();
        $data['user_id'] = Auth::id();

        return $this->repository->create($data);
    }

    public function update(int $id, ContactDTO $dto): Contact
    {
        $contact = $this->repository->findById($id);

        if (!$contact) {
            throw new \Exception('Contact not found', 404);
        }

        if ($contact->user_id !== Auth::id()) {
            throw new \Exception('Unauthorized', 403);
        }

        $data = $dto->toArray();
        $this->repository->update($contact, $data);

        return $contact->fresh();
    }

    public function delete(int $id): bool
    {
        $contact = $this->repository->findById($id);

        if (!$contact) {
            throw new \Exception('Contact not found', 404);
        }

        if ($contact->user_id !== Auth::id()) {
            throw new \Exception('Unauthorized', 403);
        }

        return $this->repository->delete($contact);
    }

    public function phoneExists(string $phone, ?int $excludeId = null): bool
    {
        $contact = $this->repository->findByPhoneAndUser(Auth::id(), $phone);

        return $contact && (!$excludeId || $contact->id !== $excludeId);
    }
}
