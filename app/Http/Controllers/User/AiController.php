<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\AiService;
use Illuminate\Http\Request;

class AiController extends Controller
{
    public function bio(Request $request, AiService $ai)
    {
        $data = $request->validate([
            'about' => ['required', 'string', 'max:500'],
        ]);

        if (! $ai->enabled()) {
            return response()->json(['error' => __('AI is not enabled.')], 422);
        }

        return response()->json(['text' => $ai->bio($data['about'])]);
    }

    public function title(Request $request, AiService $ai)
    {
        $data = $request->validate([
            'url' => ['required', 'url'],
        ]);

        if (! $ai->enabled()) {
            return response()->json(['error' => __('AI is not enabled.')], 422);
        }

        return response()->json(['text' => $ai->title($data['url'])]);
    }
}
