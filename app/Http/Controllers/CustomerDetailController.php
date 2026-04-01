<?php

namespace App\Http\Controllers;

use App\Models\CustomerDetail;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class CustomerDetailController extends Controller
{
    /**
     * Display a listing of the customer details.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $customers = CustomerDetail::all();

        return response()->json([
            'success' => true,
            'data' => $customers
        ], 200);
    }

    /**
     * Store a newly created customer detail in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'company_name' => 'required|string|max:255',
                'email' => 'required|email|unique:customer_details,email',
                'phoneno' => 'required|string|max:20',
                'is_notify' => 'boolean',
                'interested_in' => 'nullable|string|max:255',
                'city' => 'required|string|max:255',
            ]);

            $customer = CustomerDetail::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Customer detail created successfully',
                'data' => $customer
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Display the specified customer detail.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $customer = CustomerDetail::find($id);

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer detail not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $customer
        ], 200);
    }

    /**
     * Update the specified customer detail in storage.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $customer = CustomerDetail::find($id);

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer detail not found'
            ], 404);
        }

        try {
            $validated = $request->validate([
                'company_name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|unique:customer_details,email,' . $id,
                'phoneno' => 'sometimes|required|string|max:20',
                'is_notify' => 'sometimes|boolean',
                'interested_in' => 'nullable|string|max:255',
                'city' => 'sometimes|required|string|max:255',
            ]);

            $customer->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Customer detail updated successfully',
                'data' => $customer
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Remove the specified customer detail from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $customer = CustomerDetail::find($id);

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer detail not found'
            ], 404);
        }

        $customer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Customer detail deleted successfully'
        ], 200);
    }
}
