<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\Response;

class MissingPageController extends Controller
{
    public function __invoke(): never
    {
        abort(Response::HTTP_NOT_FOUND);
    }
}
