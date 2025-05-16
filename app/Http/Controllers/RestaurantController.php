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

        $data = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($keyword) {
            $apiKey = env('GOOGLE_API_KEY');
            $response = Http::get('https://maps.googleapis.com/maps/api/place/textsearch/json', [
                'query' => $keyword . ' restaurants',
                'key' => $apiKey,
                'language' => app()->getLocale()
            ]);
            return $response->json();
        });

        // ดัดแปลงข้อมูลให้ frontend ใช้ lat/lng ได้
        $data['results'] = collect($data['results'])->map(function ($item) {
            return [
                'name' => $item['name'] ?? '',
                'formatted_address' => $item['formatted_address'] ?? '',
                'place_id' => $item['place_id'] ?? '',
                'geometry' => [
                    'location' => [
                        'lat' => $item['geometry']['location']['lat'],
                        'lng' => $item['geometry']['location']['lng'],
                    ]
                ]
            ];
        });

        return response()->json($data);
    }
}
