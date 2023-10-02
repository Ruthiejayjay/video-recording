<?php

namespace App\Http\Controllers;

use App\Models\Video;
use App\Http\Requests\StoreVideoRequest;
use App\Http\Requests\UpdateVideoRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use getID3;
use Illuminate\Support\Facades\Http;

class VideoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $videos = Video::get();

        return response()->json(['data' => $videos]);
    }

    // API Key FE95V1MH8SRLGW16EQB9S2IFPLKFIQTC

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVideoRequest $request)
    {
        $video = $request->file('video-path');
        $video_name = time() . "." . $video->getClientOriginalName();
        $video->storeAs('videos', $video_name, ['s3', 'public']);
        $localVideo_name = time() . "." . $video->getClientOriginalName();
        $video->storeAs('vids', $localVideo_name, 'local');

        $videoInByte = $video->getSize() / (1024 * 1024);
        $video_size = round($videoInByte, 2) . "mb";
        $path = Storage::path("videos/" . $video_name, ['s3', 'public']);
        $fullpath = "https://hng-video-upload.s3.amazonaws.com/" . $path;

        $url = fopen($fullpath, 'r');
        $response = Http::withOptions([
            'curl' => [CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1], // Set HTTP/1.1
        ])->withHeaders(['Authorization' => 'Bearer FE95V1MH8SRLGW16EQB9S2IFPLKFIQTC'])
            ->attach('file', $url) // Replace 'your-video-file.mp4' with the actual filename
            ->post('https://transcribe.whisperapi.com', [
                'fileType' => 'mp4',
                'diarization' => 'false',
                'task' => 'transcribe',
            ]);

        $localVideoPath = storage_path("app/vids/" . $localVideo_name);
        // dd($localVideoPath);
        $getID3 = new getID3();
        $video_file = $getID3->analyze($localVideoPath);
        $duration_seconds = isset($video_file['playtime_string']) ? $video_file['playtime_string'] : '00:00';

        $video = Video::create([
            'video-path' => $fullpath,
            'name' => $video_name,
            'length' => '',
            'size' => $video_size,
            'transcript' => $response['text'],
            'uploaded_at' => Carbon::now()->toIso8601ZuluString(),
        ]);

        $localVideoToDelete = 'app/vids/' . $localVideo_name;
        $deleteLocalVideo = storage_path($localVideoToDelete);
        unlink($deleteLocalVideo);

        return response()->json(
            [
                'status_code' => Response::HTTP_CREATED,
                'status' => 'success',
                'message' => 'Video created successfully',
                'data' => $video
            ],
            Response::HTTP_CREATED
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(int $Id)
    {
        if (!Video::find($Id)) {
            return response()->json([
                'message' => 'Video not found',
                'statusCode' => 404,
            ], 404);
        }

        $videoData = Video::where('id', $Id)->first();

        $videoResponse = $videoData->only([
            'video-path',
            'name',
            'id'
        ]);

        return response()->json([
            'message' => 'Video retrived succesfully',
            'statusCode' => 200,
            'data' => $videoResponse
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Video $video)
    {
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateVideoRequest $request, Video $video)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $Id)
    {
        if (!Video::find($Id)) {
            return response()->json([
                'message' => 'Video not found',
                'statusCode' => 404,
            ], 404);
        }

        $video = Video::where('id', $Id)->first();

        $video->delete();

        return response()->json('Deleted', 204);
    }
}
