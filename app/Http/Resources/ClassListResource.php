<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Wraps one row of ReportCardService::classList(). No calculations here.
class ClassListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource['student']->id,
            'lrn' => $this->resource['student']->lrn,
            'name' => $this->resource['student']->fullName(),
            'general_average' => $this->resource['average'],
            'remark' => $this->resource['remark'],
        ];
    }
}
