<?php

namespace App\Http\Controllers;

use App\Models\Video;
use App\Http\Requests\StoreVideoRequest;
use App\Http\Requests\UpdateVideoRequest;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use getID3;

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

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVideoRequest $request)
    {
        $validation = $request->validated();
        $video = $request->file('video-path');
        $video_name = time() . "." . $video->getClientOriginalName();
        $video->storeAs('videos', $video_name, ['s3', 'public']);

        $videoInByte = $video->getSize() / (10244 * 1024);
        $video_size = round($videoInByte, 2) . "mb";
        $path = Storage::path("videos/" . $video_name, ['s3', 'public']);
        $fullpath = "https://hng-video-upload.s3.amazonaws.com/" . $path;
        // $getID3 = new \getID3;
        // $video_file = $getID3->analyze($fullpath);
        // $duration_seconds = $video_file['playtime_seconds'];
        

        $video = Video::create([
            'video-path' => $fullpath,
            'name' => $video_name,
            'length' => '',
            'size' => $video_size
        ]);
        
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

        return response()->json('Deleted',204);
    }
}
