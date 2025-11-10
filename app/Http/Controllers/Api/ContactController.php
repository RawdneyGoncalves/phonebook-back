<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\DTOs\ContactDTO;
use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\UpdateContactRequest;
use App\Models\Contact;
use App\Services\ContactService;
use App\Services\ImageUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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

            if ($query) {
                $contacts = $this->contactService->search($query, $perPage);
            } else {
                $contacts = $this->contactService->getAll($perPage);
            }

            return response()->json([
                'data' => $contacts->items(),
                'pagination' => [
                    'total' => $contacts->total(),
                    'per_page' => $contacts->perPage(),
                    'current_page' => $contacts->currentPage(),
                    'last_page' => $contacts->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching contacts', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Erro ao buscar contatos'], 500);
        }
    }

    public function store(StoreContactRequest $request): JsonResponse
    {
        try {
            $userId = Auth::id();

            if (!$userId) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $validated = $request->validated();

            $dto = ContactDTO::fromArray($validated);
            $contact = $this->contactService->create($dto);

            if ($request->hasFile('image')) {
                $imagePath = $this->imageUploadService->upload($request->file('image'));
                $contact->update(['image_path' => $imagePath]);
                $contact->refresh();
            }

            return response()->json(['data' => $contact], 201);
        } catch (\Exception $e) {
            Log::error('Error creating contact', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show(Contact $contact): JsonResponse
    {
        try {
            $userId = Auth::id();

            if (!$userId) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            if ($contact->user_id !== $userId) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            return response()->json(['data' => $contact]);
        } catch (\Exception $e) {
            Log::error('Error fetching contact', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Erro ao buscar contato'], 500);
        }
    }

    public function update(UpdateContactRequest $request, Contact $contact): JsonResponse
    {
        try {
            $userId = Auth::id();

            if (!$userId) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            if ($contact->user_id !== $userId) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $validated = $request->validated();

            if ($request->hasFile('image')) {
                $this->imageUploadService->delete($contact->image_path);
                $imagePath = $this->imageUploadService->upload($request->file('image'));
                $validated['image_path'] = $imagePath;
            }

            $dto = ContactDTO::fromArray($validated);
            $updated = $this->contactService->update($contact->id, $dto);

            return response()->json(['data' => $updated]);
        } catch (\Exception $e) {
            Log::error('Error updating contact', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy(Contact $contact): JsonResponse
    {
        try {
            $userId = Auth::id();

            if (!$userId) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            if ($contact->user_id !== $userId) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $this->imageUploadService->delete($contact->image_path);
            $this->contactService->delete($contact->id);

            return response()->json(null, 204);
        } catch (\Exception $e) {
            Log::error('Error deleting contact', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
