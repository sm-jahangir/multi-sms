<?php

namespace MultiSms\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use MultiSms\Models\SmsTemplate;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class TemplateController extends Controller
{
    /**
     * Get all templates
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = SmsTemplate::query();

            // Filter by active status
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // Filter by tags
            if ($request->has('tags')) {
                $tags = is_array($request->tags) ? $request->tags : [$request->tags];
                $query->whereJsonContains('tags', $tags);
            }

            // Search by name or key
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('key', 'like', '%' . $search . '%');
                });
            }

            $templates = $query->orderBy('name')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $templates
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new template
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'key' => 'required|string|max:255|unique:sms_templates,key',
                'name' => 'required|string|max:255',
                'body' => 'required|string|max:1600',
                'tags' => 'nullable|array',
                'tags.*' => 'string|max:50',
                'description' => 'nullable|string|max:500',
                'is_active' => 'boolean'
            ]);

            // Extract variables from template body
            $variables = SmsTemplate::extractVariables($validated['body']);

            $template = SmsTemplate::create([
                'key' => $validated['key'],
                'name' => $validated['name'],
                'body' => $validated['body'],
                'tags' => $validated['tags'] ?? [],
                'variables' => $variables,
                'description' => $validated['description'] ?? null,
                'is_active' => $validated['is_active'] ?? true
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Template created successfully',
                'data' => $template
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific template
     */
    public function show(SmsTemplate $template): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $template
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a template
     */
    public function update(Request $request, SmsTemplate $template): JsonResponse
    {
        try {
            $validated = $request->validate([
                'key' => [
                    'sometimes',
                    'string',
                    'max:255',
                    Rule::unique('sms_templates', 'key')->ignore($template->id)
                ],
                'name' => 'sometimes|string|max:255',
                'body' => 'sometimes|string|max:1600',
                'tags' => 'nullable|array',
                'tags.*' => 'string|max:50',
                'description' => 'nullable|string|max:500',
                'is_active' => 'boolean'
            ]);

            // If body is updated, extract new variables
            if (isset($validated['body'])) {
                $validated['variables'] = SmsTemplate::extractVariables($validated['body']);
            }

            $template->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Template updated successfully',
                'data' => $template->fresh()
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a template
     */
    public function destroy(SmsTemplate $template): JsonResponse
    {
        try {
            // Check if template is being used by campaigns
            $campaignCount = $template->campaigns()->count();
            if ($campaignCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot delete template. It is being used by {$campaignCount} campaign(s)."
                ], 400);
            }

            $template->delete();

            return response()->json([
                'success' => true,
                'message' => 'Template deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Preview template with variables
     */
    public function preview(Request $request, SmsTemplate $template): JsonResponse
    {
        try {
            $validated = $request->validate([
                'variables' => 'nullable|array'
            ]);

            $variables = $validated['variables'] ?? [];
            $preview = $template->render($variables);

            return response()->json([
                'success' => true,
                'data' => [
                    'original' => $template->body,
                    'preview' => $preview,
                    'variables' => $template->variables,
                    'provided_variables' => $variables,
                    'character_count' => strlen($preview),
                    'sms_count' => $template->getEstimatedSmsCount($variables)
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate template variables
     */
    public function validateVariables(Request $request, SmsTemplate $template): JsonResponse
    {
        try {
            $validated = $request->validate([
                'variables' => 'required|array'
            ]);

            $variables = $validated['variables'];
            $validation = $template->validateVariables($variables);

            return response()->json([
                'success' => true,
                'data' => [
                    'is_valid' => $validation['is_valid'],
                    'missing_variables' => $validation['missing'],
                    'extra_variables' => $validation['extra'],
                    'required_variables' => $template->variables
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get template by key
     */
    public function getByKey(string $key): JsonResponse
    {
        try {
            $template = SmsTemplate::byKey($key)->first();

            if (!$template) {
                return response()->json([
                    'success' => false,
                    'message' => 'Template not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $template
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle template active status
     */
    public function toggleStatus(SmsTemplate $template): JsonResponse
    {
        try {
            $template->update([
                'is_active' => !$template->is_active
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Template status updated successfully',
                'data' => [
                    'id' => $template->id,
                    'is_active' => $template->is_active
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}