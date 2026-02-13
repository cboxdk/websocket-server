<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReverbApplication extends Model
{
    /** @use HasFactory<\Database\Factories\ReverbApplicationFactory> */
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $attributes = [
        'allowed_origins' => '["*"]',
    ];

    protected $fillable = [
        'id',
        'key',
        'secret',
        'name',
        'allowed_origins',
        'enable_client_messages',
        'max_connections',
        'max_message_size',
        'options',
    ];

    protected function casts(): array
    {
        return [
            'allowed_origins' => 'array',
            'enable_client_messages' => 'boolean',
            'max_connections' => 'integer',
            'max_message_size' => 'integer',
            'options' => 'array',
        ];
    }
}
