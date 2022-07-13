<?php

namespace Altra\Context\Tests\TestSupport\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TestClassResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'column_1' => $this->column_1,
        ];
    }
}
