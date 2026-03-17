<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AreaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'manager' => new UserResource($this->whenLoaded('manager')),
            'active' => $this->active,
            'members_count' => $this->whenCounted('activeMembers'),
            'members' => AreaMemberResource::collection($this->whenLoaded('activeMembers')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
