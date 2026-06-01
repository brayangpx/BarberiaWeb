<?php

namespace App\Http\Controllers;

use App\Services\InternalNotificationService;

class NotificationController extends Controller
{
    public function index(InternalNotificationService $service)
    {
        return response()->json($service->ultimas(10));
    }
}
