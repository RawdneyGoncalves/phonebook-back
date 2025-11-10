<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\DTOs\ContactDTO;
use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\UpdateContactRequest;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use App\Services\ContactService;
use App\Services\ImageUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

final class ContactController
{
    public function __construct(
        private ContactService $contactService,
        private ImageUploadService $imageUploadService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $userId = Auth::id();

            if (!$userId) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $query = $request->query('q');
            $perPage = (int) $request->query('per_page', 15);
            $contacts = $query
                ? $this->contactService->search($query, $perPage)
                : $this->contactService->getAll($perPage);

            return response()->json([
                'data' => ContactResource::collection($contacts->items()),
                'pagination' => [
                    'total' => $contacts->total(),
                    'per_page' => $contacts->perPage(),
                    'current_page' => $contacts->currentPage(),
                    'last_page' => $contacts->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar contatos', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Erro ao buscar contatos'], 500);
        }
    }

    public function store(StoreContactRequest $request): JsonResponse
    {
        try {
            $dto = ContactDTO::fromArray($request->validated());
            $contact = $this->contactService->create($dto);

            if ($request->hasFile('image')) {
                $contact->update([
                    'image_path' => $this->imageUploadService->upload($request->file('image'))
                ]);
                $contact->refresh();
            }

            return response()->json(['data' => new ContactResource($contact)], 201);
        } catch (\Exception $e) {
            Log::error('Erro ao criar contato', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erro ao criar contato'], 500);
        }
    }

    public function show(Contact $contact): JsonResponse
    {
        return response()->json(['data' => new ContactResource($contact)]);
    }

    public function update(UpdateContactRequest $request, Contact $contact): JsonResponse
    {
        try {
            $data = $request->validated();

            if ($request->hasFile('image')) {
                if ($contact->image_path) {
                    $this->imageUploadService->delete($contact->image_path);
                }
                $data['image_path'] = $this->imageUploadService->upload($request->file('image'));
            }

            $updated = $this->contactService->update($contact->id, ContactDTO::fromArray($data));

            return response()->json(['data' => new ContactResource($updated)]);
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar contato', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erro ao atualizar contato'], 500);
        }
    }

    public function destroy(Contact $contact): JsonResponse
    {
        try {
            if ($contact->image_path) {
                $this->imageUploadService->delete($contact->image_path);
            }

            $this->contactService->delete($contact->id);

            return response()->json(null, 204);
        } catch (\Exception $e) {
            Log::error('Erro ao deletar contato', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erro ao deletar contato'], 500);
        }
    }
}
