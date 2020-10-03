<?php

namespace Tracking\Http\Controllers\Admin\Metrics;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Tracking\Models\Metrics\LarametricsNotification;

class NotificationController extends Controller
{
    
    public function index(Request $request): \Illuminate\View\View
    {
        $notifications = LarametricsNotification::all();
        
        return view(
            'rica.larametrics::notifications.index', [
            'notifications' => $notifications,
            'pageTitle' => 'Notifications'
            ]
        );
    }

    public function update(Request $request): \Illuminate\Http\RedirectResponse
    {
        LarametricsNotification::truncate();
        foreach(json_decode($request->input('notifications')) as $notification) {
            $notificationData = array(
                'action' => $notification->action,
                'filter' => $notification->filter,
                'notify_by' => $notification->notify_by
            );
            try {
                LarametricsNotification::create($notificationData);
            } catch(\Exception $e) {
            }
        }

        return redirect()->route('rica.larametrics::notifications.index');
    }

}
