<?php

namespace App\Http\Helpers;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

class ResponseHelper
{
    public static function success($data = null, $message = '', $status = 200)
    {
        $body = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        // Nesting a resource collection inside this plain array stops Laravel's
        // PaginatedResourceResponse from ever running, so current_page /
        // last_page / total were silently dropped from every list endpoint.
        // The dashboard's pager reads last_page, saw the fabricated 1, and hid
        // itself — so every list was capped at 10 rows with no way to page, and
        // "showing 10 of 10" was a lie.
        //
        // Emitted as a SIBLING key rather than by wrapping `data`: the shipped
        // mobile app reads `data` as a flat list, and changing that shape would
        // break it until every handset updates.
        if ($meta = self::paginationMeta($data)) {
            $body['meta'] = $meta;
        }

        return response()->json($body, $status);
    }

    /** Pagination figures for a paginator, however it arrives. */
    private static function paginationMeta($data): ?array
    {
        $paginator = $data instanceof AnonymousResourceCollection
            ? $data->resource
            : $data;

        if (! $paginator instanceof LengthAwarePaginator) {
            return null;
        }

        return [
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
            'per_page'     => $paginator->perPage(),
            'total'        => $paginator->total(),
            'from'         => $paginator->firstItem(),
            'to'           => $paginator->lastItem(),
        ];
    }

    public static function error($message = '', $errors = [], $status = 422)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}
