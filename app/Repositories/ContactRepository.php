<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Contact;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ContactRepository
{
    public function __construct(private Contact $model) {}

    public function findById(int $id): ?Contact
    {
        return $this->model->find($id);
    }

    public function findByPhone(string $phone): ?Contact
    {
        return $this->model->where('phone', $phone)->first();
    }

    public function all(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->orderBy('name', 'asc')
            ->paginate($perPage);
    }

    public function search(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%");
            })
            ->orderBy('name', 'asc')
            ->paginate($perPage);
    }

    public function create(array $data): Contact
    {
        return $this->model->create($data);
    }

    public function update(Contact $contact, array $data): bool
    {
        return $contact->update($data);
    }

    public function delete(Contact $contact): bool
    {
        return $contact->delete();
    }

    public function exists(int $id): bool
    {
        return $this->model->where('id', $id)->exists();
    }
}
