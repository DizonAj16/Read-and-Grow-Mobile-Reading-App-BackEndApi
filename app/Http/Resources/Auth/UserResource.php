<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /** @return array<string,mixed> */
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'username'   => $this->username,
            'role'       => $this->role,
            'created_at' => optional($this->created_at)->toDateTimeString(),
        ];
    }
}
