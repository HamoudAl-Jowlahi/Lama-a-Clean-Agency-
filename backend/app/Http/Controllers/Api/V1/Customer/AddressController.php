<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddressRequest;
use App\Http\Resources\AddressResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** عناوين العميل — كل الاستعلامات عبر $customer->addresses() (لا وصول لعناوين غيره). */
class AddressController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $customer = $request->user()->customer;

        return AddressResource::collection($customer->addresses()->latest('id')->get())
            ->additional(['meta' => ['default_address_id' => $customer->default_address_id]]);
    }

    public function store(AddressRequest $request): JsonResponse
    {
        $customer = $request->user()->customer;
        $address = $customer->addresses()->create($request->safe()->except('make_default'));

        if ($request->boolean('make_default') || ! $customer->default_address_id) {
            $customer->update(['default_address_id' => $address->id]);
        }

        return (new AddressResource($address))->response()->setStatusCode(201);
    }

    public function update(AddressRequest $request, int $id): AddressResource
    {
        $customer = $request->user()->customer;
        $address = $customer->addresses()->findOrFail($id);
        $address->update($request->safe()->except('make_default'));

        if ($request->boolean('make_default')) {
            $customer->update(['default_address_id' => $address->id]);
        }

        return new AddressResource($address);
    }

    /** حذف ناعم: الطلبات السابقة تحتفظ بنسخة العنوان. */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $customer = $request->user()->customer;
        $address = $customer->addresses()->findOrFail($id);
        $address->delete();

        if ($customer->default_address_id === $address->id) {
            $customer->update(['default_address_id' => $customer->addresses()->latest('id')->value('id')]);
        }

        return response()->json(['message' => __('api.deleted')]);
    }
}
