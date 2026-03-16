<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateMessageTemplateRequest;
use App\Http\Resources\MessageTemplateResource;
use App\Models\MessageTemplate;
use Illuminate\Http\JsonResponse;

class MessageTemplateController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', MessageTemplate::class);

        $templates = MessageTemplate::orderBy('name')->get();

        return response()->json(['data' => MessageTemplateResource::collection($templates)]);
    }

    public function show(MessageTemplate $messageTemplate): MessageTemplateResource
    {
        $this->authorize('view', $messageTemplate);

        return new MessageTemplateResource($messageTemplate);
    }

    public function update(UpdateMessageTemplateRequest $request, MessageTemplate $messageTemplate): MessageTemplateResource
    {
        $messageTemplate->update($request->validated());

        return new MessageTemplateResource($messageTemplate);
    }
}
