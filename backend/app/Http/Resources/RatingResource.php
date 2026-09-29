<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\FormatsValues;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * التقييم بدون بيانات العميل (العاملة ترى التقييمات دون اسم صاحبها).
 *
 * @mixin \App\Models\Rating
 */
class RatingResource extends JsonResource
{
    use FormatsValues;

    public function toArray(Request $request): array
    {
        return [
            'service_score' => $this->service_score,
            'worker_score' => $this->worker_score,
            'comment' => $this->comment,
            'created_at' => $this->datetime($this->created_at),
        ];
    }
}
