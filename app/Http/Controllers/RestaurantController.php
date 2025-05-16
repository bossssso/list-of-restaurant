<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class RestaurantController extends Controller
{
    public function search(Request $request)
    {
        $keyword = $request->input('keyword', 'Bang Sue');
        $cacheKey = 'restaurants_' . md5($keyword);
        $locale = app()->getLocale();
        $apiKey = env('GOOGLE_API_KEY');

        $data = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($keyword, $apiKey, $locale) {
            $response = Http::get('https://maps.googleapis.com/maps/api/place/textsearch/json', [
                'query' => $keyword . ' restaurants',
                'key' => $apiKey,
                'language' => $locale
            ]);

            $places = $response->json()['results'] ?? [];

            return collect($places)->map(function ($item) {
                return [
                    'name' => $item['name'] ?? '',
                    'formatted_address' => $item['formatted_address'] ?? '',
                    'place_id' => $item['place_id'] ?? '',
                    'rating' => $item['rating'] ?? null,
                    'user_ratings_total' => $item['user_ratings_total'] ?? null,
                    'geometry' => [
                        'location' => [
                            'lat' => $item['geometry']['location']['lat'] ?? null,
                            'lng' => $item['geometry']['location']['lng'] ?? null,
                        ],
                    ],
                ];
            });
        });

        return response()->json(['results' => $data]);
    }
}
