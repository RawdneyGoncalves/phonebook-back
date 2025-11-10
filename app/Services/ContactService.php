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
        $userId = Auth::id();

        return Contact::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function search(string $query, int $perPage = 15): LengthAwarePaginator
    {
        if (empty($query)) {
            return $this->getAll($perPage);
        }

        $userId = Auth::id();

        return Contact::where('user_id', $userId)
            ->where(function ($q) use ($query) {
                $q->where('name', 'ilike', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%")
                  ->orWhere('email', 'ilike', "%{$query}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
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

        return Contact::create($data);
    }

    public function update(int $id, ContactDTO $dto): Contact
    {
        $contact = Contact::findOrFail($id);

        if ($contact->user_id !== Auth::id()) {
            throw new \Exception('Unauthorized', 403);
        }

        $contact->update($dto->toArray());

        return $contact->fresh();
    }

    public function delete(int $id): bool
    {
        $contact = Contact::findOrFail($id);

        if ($contact->user_id !== Auth::id()) {
            throw new \Exception('Unauthorized', 403);
        }

        return $contact->delete();
    }

    public function phoneExists(string $phone, ?int $excludeId = null): bool
    {
        $query = Contact::where('user_id', Auth::id())
            ->where('phone', $phone);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
