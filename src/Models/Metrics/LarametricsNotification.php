<?php

namespace Tracking\Models\Metrics;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class LarametricsNotification extends Model
{
    use Notifiable;

    protected $table = 'larametrics_notifications';

    /**
     * @var array
     */
    public $guarded = [];

    public function routeNotificationForMail($notification = null)
    {
        return \Illuminate\Support\Facades\Config::get('larametrics.notificationMethods')['email'];
    }
    
    public function routeNotificationForSlack($notification = null)
    {
        return \Illuminate\Support\Facades\Config::get('larametrics.notificationMethods')['slack'];
    }
}