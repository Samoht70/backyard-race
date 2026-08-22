<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class RenderErrorPage
{
    private const PAGE_EXPIRED = 419;

    private const RENDERED_IN_SITE = [
        Response::HTTP_NOT_FOUND,
        Response::HTTP_FORBIDDEN,
        self::PAGE_EXPIRED,
        Response::HTTP_INTERNAL_SERVER_ERROR,
    ];

    public function __invoke(Response $response, Throwable $exception, Request $request): Response
    {
        $status = $response->getStatusCode();

        if ($response instanceof JsonResponse) {
            return $response;
        }

        if (! in_array($status, self::RENDERED_IN_SITE, true)) {
            return $response;
        }

        if ($status === Response::HTTP_INTERNAL_SERVER_ERROR && app()->hasDebugModeEnabled()) {
            return $response;
        }

        return Inertia::render('Error', ['status' => $status])
            ->toResponse($request)
            ->setStatusCode($status);
    }
}
