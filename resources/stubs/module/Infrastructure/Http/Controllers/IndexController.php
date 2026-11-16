<?php

namespace DummyNamespace\Infrastructure\Http\Controllers;

use DummyNamespace\Application\UseCase\DummyList\DummyListRequest;
use DummyNamespace\Application\UseCase\DummyList\DummyListUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IndexController
{
    public function index(Request $request, DummyListUseCase $useCase): JsonResponse
    {
        $page = $request->query('page');
        $perPage = $request->query('perPage');

        $result = $useCase->handle(new DummyListRequest(
            page: is_numeric($page) && (int) $page > 0 ? (int) $page : 1,
            perPage: is_numeric($perPage) && (int) $perPage > 0 ? (int) $perPage : 15,
        ));

        return response()->json([
            'data' => $result->items,
            'total' => $result->total,
        ]);
    }
}
