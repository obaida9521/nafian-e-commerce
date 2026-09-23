<?php

namespace App\Models;

use Database\Factories\SubscriberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * An email or phone number signed up for the newsletter or campaign SMS alerts.
 */
class Subscriber extends Model
{
    /** @use HasFactory<SubscriberFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'contact',
        'channel',
        'source',
    ];
}
