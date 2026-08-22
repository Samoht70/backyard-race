<?php

namespace App\Http\Controllers;

use App\Services\QueueHealth\QueueConsumption;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class QueueHealthController extends Controller
{
    public function __invoke(QueueConsumption $consumption): JsonResponse
    {
        $state = $consumption->state();

        return response()->json(
            ['queue' => $state->value],
            $state->isStalled() ? Response::HTTP_SERVICE_UNAVAILABLE : Response::HTTP_OK,
        );
    }
}
