<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SynthesisStage extends Model
{
    protected $fillable = [
        'name',
        'brief_description',
        'lifecycle_status',
        'media_image_url',
        'media_video_url',
        'yield_percentage',
        'reaction_temperature_celsius',
        'chemist_id',
    ];

    // Связь для подсчета лайков
    public function likingChemists(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            Chemist::class,
            'synthesis_stage_likes',
            'synthesis_stage_id',
            'chemist_id'
        );
    }
}
