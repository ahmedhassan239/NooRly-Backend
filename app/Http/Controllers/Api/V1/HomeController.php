<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Home\GetHomeDashboardDataAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\HomeResource;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __invoke(Request $request, GetHomeDashboardDataAction $action)
    {
        $data = $action->execute($request->user());

        return new HomeResource($data);
    }
}
