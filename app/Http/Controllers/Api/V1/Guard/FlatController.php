<?php

namespace App\Http\Controllers\Api\V1\Guard;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\FlatResource;
use App\Models\Tenant\Flat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Flat picker for the guard app's walk-in check-in form
 * (screens/gatekeeper_visitor_checkin_screen.dart) — a resident only ever
 * picks from their own flat(s) client-side (auth.user.flats), but a guard
 * needs to find any flat in the society, hence this endpoint.
 */
class FlatController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Flat::active()->with('block');
        $closedOnly = $request->boolean('closed');

        // "Closed houses" list: flats whose resident switched on House
        // Closed in Visitor Settings - the gate must not let anyone in.
        if ($closedOnly) {
            $query->where('house_closed', true)->with(['residents' => fn ($r) => $r->active()->with('user')]);
        }

        if ($search = $request->string('search')->trim()->value()) {
            $query->where(function ($q) use ($search) {
                $q->where('flat_number', 'like', "%{$search}%")
                    ->orWhere('owner_name', 'like', "%{$search}%")
                    ->orWhereHas('block', fn ($b) => $b->where('name', 'like', "%{$search}%"));
            });
        }

        // `all`: every flat at once, for the check-in form's block -> flat
        // picker; a plain search stays capped at 50 matches.
        $limit = ($closedOnly || $request->boolean('all')) ? 2000 : 50;
        $flats = $query->orderBy('flat_number')->limit($limit)->get();

        return $this->ok(FlatResource::collection($flats));
    }
}
