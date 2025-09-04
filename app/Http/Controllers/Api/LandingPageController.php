<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LandingPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class LandingPageController extends Controller
{
    public function show(Request $request, string $slug)
    {
        $locale = $request->query('locale', 'en');
        $preview = filter_var($request->query('preview', false), FILTER_VALIDATE_BOOL);

        $cacheKey = "landing:page:{$slug}:{$locale}:".($preview ? 'preview' : 'live');

        $page = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($slug, $locale, $preview) {
            $q = LandingPage::query()->where('slug', $slug)->where('locale', $locale);
            if (! $preview) {
                $q->where('is_published', true);
            }

            return $q->first();
        });

        if (! $page) {
            return response()->json([
                'ok' => false,
                'code' => 'NOT_FOUND',
                'message' => 'Page not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'slug' => $page->slug,
            'locale' => $page->locale,
            'title' => $page->title,
            'meta' => [
                'title' => $page->meta_title,
                'description' => $page->meta_description,
            ],
            'sections' => $page->sections ?? [],
            'is_published' => (bool) $page->is_published,
            'published_at' => optional($page->published_at)?->toIso8601String(),
            'updated_at' => $page->updated_at->toIso8601String(),
            'version' => (int) $page->version,
        ])->header('Cache-Control', 'public, max-age=60, s-maxage=300');
    }
}
