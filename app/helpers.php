<?php

use Illuminate\Support\Facades\Http;

function createCoursePremium($request)
{
    $url = env('SERVIE_COURSE_URL') . 'api/my-courses/premium';

    try {
        $response = Http::post($url, $request);
        $data = $response->json();
        $data["http_code"] = $response->getStatusCode();
        return $data;
    } catch (\Throwable $th) {
        return [
            "status" => "error",
            "message" => $th->getMessage(),
            "http_code" => 500
        ];
    }
}