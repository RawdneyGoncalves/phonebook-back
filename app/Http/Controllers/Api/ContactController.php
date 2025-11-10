<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\DTOs\ContactDTO;
use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\UpdateContactRequest;
use App\Http\Resources\ContactResource;
use App\Services\ContactService;
use App\Services\ImageUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ContactController
{
    public function __construct(
        private ContactService $service,
        private ImageUploadService $imageService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = $request->query('q');
        $contacts = $query
            ? $this->service->search($query)
            : $this->service->getAll();

        return response()->json([
            'data' => ContactResource::collection($contacts->items()),
            'pagination' => [
                'total' => $contacts->total(),
                'per_page' => $contacts->perPage(),
                'current_page' => $contacts->currentPage(),
                'last_page' => $contacts->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $contact = $this->service->getById($id);

        if (!$contact) {
            return response()->json(['error' => 'Contact not found'], 404);
        }

        return response()->json(['data' => new ContactResource($contact)]);
    }

    public function store(StoreContactRequest $request): JsonResponse
    {
        $data = [
            'name' => $request->input('name'),
            'phone' => $request->input('phone'),
            'email' => $request->input('email'),
        ];

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->imageService->upload($request->file('image'));
        }

        $contact = $this->service->create(ContactDTO::fromArray($data));

        return response()->json(['data' => new ContactResource($contact)], 201);
    }

    public function update(int $id, UpdateContactRequest $request): JsonResponse
    {
        $contact = $this->service->getById($id);

        if (!$contact) {
            return response()->json(['error' => 'Contact not found'], 404);
        }

        $data = [
            'name' => $request->input('name'),
            'phone' => $request->input('phone'),
            'email' => $request->input('email'),
        ];

        if ($request->hasFile('image')) {
            $this->imageService->delete($contact->image_path);
            $data['image_path'] = $this->imageService->upload($request->file('image'));
        }

        $contact = $this->service->update($id, ContactDTO::fromArray($data));

        return response()->json(['data' => new ContactResource($contact)]);
    }

    public function destroy(int $id): JsonResponse
    {
        $contact = $this->service->getById($id);

        if (!$contact) {
            return response()->json(['error' => 'Contact not found'], 404);
        }

        if ($contact->image_path) {
            $this->imageService->delete($contact->image_path);
        }

        $this->service->delete($id);

        return response()->json(null, 204);
    }
}
