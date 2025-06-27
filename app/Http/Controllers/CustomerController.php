<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerStoreRequest;
use App\Http\Requests\CustomerUpdateRequest;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $customersQuery = Customer::query();

        // Check if the request has a 'search' parameter and is not empty
        if ($request->has('search') && !empty($request->search)) {

            // This will search in first_name, last_name, email, and phone fields
            $customersQuery->where(function ($query) use ($request) {
                $query->where('first_name', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('last_name', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('email', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('phone', 'LIKE', '%' . $request->search . '%');
            });
        }

        $customers = $customersQuery->latest()->paginate(10);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Customers retrieved successfully',
                'data' => $customers->items(),
                'pagination' => [
                    'total' => $customers->total(),
                    'per_page' => $customers->perPage(),
                    'current_page' => $customers->currentPage(),
                    'last_page' => $customers->lastPage(),
                    'from' => $customers->firstItem(),
                    'to' => $customers->lastItem(),
                ],
            ]);
        }

        return view('customers.index', compact('customers'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('customers.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CustomerStoreRequest $request)
    {

        $customer = Customer::create($request->validated());

        if ($request->hasFile('image')) {
            $image_path = $request->file('image')->store('customers', 'public');

            $customer->image()->create([
                'url' => $image_path,
            ]);
        }

        if (!$customer) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('customer.error_creating')
                ], 500);
            }
            return redirect()->back()->with('error', __('customer.error_creating'));
        }

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => __('customer.success_creating'),
                'data' => $customer
            ], 201);
        }

        return redirect()->route('customers.index')->with('success', __('customer.success_creating'));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\Response
     */
    public function show(Customer $customer)
    {
        $customer->load([
            'orders' => function ($query) {
                $query->orderBy('created_at', 'desc');
            },
            'orders.orderItems.product'
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Customer details and order history retrieved successfully.',
            'data' => $customer
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\Response
     */
    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\Response
     */
    public function update(CustomerUpdateRequest $request, Customer $customer)
    {

        $customer->update($request->validated());

        if ($request->hasFile('image')) {

            // Delete old image
            if ($customer->image) {
                Storage::disk('public')->delete($customer->image->url);
                $customer->image()->delete();
            }

            $imagePath = $request->file('image')->store('customer_images', 'public');
            $customer->image()->create([
                'url' => $imagePath,
            ]);
        }

        // Check if the customer was updated successfully
        if (!$customer) {
            // If the request expects JSON, return a JSON response
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('customer.error_updating')
                ], 500);
            }
            // Otherwise, redirect back with an error message
            return redirect()->back()->with('error', __('customer.error_updating'));
        }

        // If the request expects JSON, return a JSON response
        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => __('customer.success_updating'),
                'data' => $customer
            ]);
        }

        // Otherwise, redirect to the customers index with a success message
        return redirect()->route('customers.index')->with('success', __('customer.success_updating'));
    }

    public function destroy(Customer $customer, Request $request)
    {
        // Check if the customer has an image and delete it
        if ($customer->image) {
            Storage::disk('public')->delete($customer->image->url);
            $customer->image()->delete();
        }

        // Attempt to delete the customer
        if (!$customer->delete()) {
            // If the request expects JSON, return a JSON response
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('customer.error_deleting')
                ], 500);
            }

            // Otherwise, redirect back with an error message
            return redirect()->back()->with('error', __('customer.error_deleting'));
        }

        // If the request expects JSON, return a JSON response
        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => __('customer.success_deleting')
            ]);
        }

        // Otherwise, redirect to the customers index with a success message
        return redirect()->route('customers.index')->with('success', __('customer.success_deleting'));
    }
}
