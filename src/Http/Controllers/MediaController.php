<?php

namespace Iquesters\UserManagement\Http\Controllers;

use Illuminate\Routing\Controller;
use Iquesters\Foundation\Constants\EntityStatus;
use Iquesters\UserManagement\Http\Controllers\Utils\MediaUtil;
use App\Models\Event;
use App\Models\MediaResources;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Iquesters\UserManagement\Models\UserMeta;

class MediaController extends Controller
{
    /**
     * Display the resource.
     */
    public function overview($id)
    {
        $media = MediaResources::find($id);
        return view('media.overview', compact('media'));
    }

    /**
     * Display a listing of the resource.
     */
    public function library()
    {
        $user = User::find(Auth::user()->id);

        return view('media.library');
    }

    /**
     * Store file and create media resource object
     * 
     * @param $file
     * 
     * @return $media_resource
     */
    protected function prepare_media_resource($file)
    {
        // generate a unique name for the file
        $fileName = time() . '_' . $file->getClientOriginalName();
        Log::info('fileName=' . $fileName);

        // Store the file in the 'media' directory
        $media_url = basename(MediaUtil::storeFile($file));
        Log::info('media_url=' . $media_url);
        $filePath = $file->getRealPath();
        $media_type = MediaUtil::getMediaType($filePath);
        Log::info('media_type=' . $media_type);

        $media_ctx = ContextController::get_context_model();

        return ([
            'uid' => Str::ulid()->toString(),
            'model' => $media_ctx->model,
            'model_id' => $media_ctx->model_id,
            'media_url' => $media_url,
            'media_type' => $media_type,
            'extra_info' => '{}',
            'status' => EntityStatus::ACTIVE
        ]);
    }

    /**
     * Prepare media_resources
     * 
     * @param $files
     */
    protected function prepare_media_resources($files)
    {
        $media_resources = [];
        foreach ($files as $index => $file) {
            $media_resource = $this->prepare_media_resource($file);
            array_push(
                $media_resources,
                $media_resource
            );
        }
        return $media_resources;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function upload(Request $request)
    {
        try {
            Log::info("Uploading media...");
            // get the file
            $files = $request->file('media');

            if (!is_array($files)) {
                $files = [$files];
            }

            // check the file validation
            $errMsgs = [
                'media.*.required' => 'Please select a file',
                'media.*.file' => 'Please select a file',
                'media.*.max' => 'Please select a file size within 2MB',
                'media.*.mimes' => 'Please choose a jpg, jpeg or png file'
            ];
            $validation_expression = [
                'media.*' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:2048']
            ];
            $validator = Validator::make($request->all(), $validation_expression, $errMsgs);
            if (!$validator->fails()) {
                $media_resources = $this->prepare_media_resources($files);
                // dd($media_resources);
                // $medias = MediaResources::insert($media_resources);
                $medias = UserMeta::updateOrCreate(
                            ['ref_parent' => Auth::user()->id, 'meta_key' => 'profile_picture'],
                            ['meta_value' => $media_resources[0]['media_url'], 'status' => 'active']
                        );

                        UserMeta::updateOrCreate(
                            ['ref_parent' => Auth::user()->id,'meta_key'   => 'profile_picture_path'],
                            ['meta_value' => "storage/media/",'status'     => 'active']
                        );
                Log::info("medias=" . $medias);

                if (isset($medias)) {
                    return redirect()->back()->with(['success' => count($files) . ' Media(s) uploaded successfully.']);
                } else {
                }
            } else {
                return redirect()->back()->withErrors($validator->errors())->withInput();
            }
        } catch (\Exception $e) {
            Log::error($e);
            // Handle other exceptions
            abort(500);
        }
    }

    /**
     * Get a newly created resource in storage
     */
    // public function download(Request $request)
    // {
    //     try {
    //         if ($request->has('media_url')) {
    //             $media_url = $request->input('media_url');
    //         }
    //         Log::info("media_url: " . $media_url);
    //         $contents = MediaUtil::getFile(basename($media_url));

    //         if (isset($contents)) {
    //             // You can do something with the file contents here, like returning it as a response
    //             return response($contents, 200, ['Content-Type' => 'application/octet-stream']);
    //         } else {
    //             abort(500);
    //         }
    //     } catch (\Exception $e) {
    //         Log::error($e);
    //         // Handle other exceptions
    //         abort(500);
    //     }
    // }

    /**
     * Remove the specified resource from storage.
     */
    public function delete($id, $route = 'media.library')
    {
        $result = MediaResources::where('id', $id)->update(['status' => EntityStatus::ACTIVE]);
        Log::info('delete status update result=' . $result);
        if ($result) {
            // $route = isset($route) || $route !== '' ? $route : 'media.library';
            // return redirect()->route($route)->with('error', 'Media deleted successfully');
            return redirect()->back()->with('error', 'Media deleted successfully');
        } else {
            return redirect()->back()->with('warning', 'Media delete failed.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id, $route = 'media.library')
    {
        $result = MediaResources::destroy($id);
        Log::info('delete result=' . $result);
        if ($result) {
            // $route = isset($route) || $route !== '' ? $route : 'media.library';
            return redirect()->route($route)->with('error', 'Media deleted successfully');
        } else {
            return redirect()->back()->with('warning', 'Media delete failed.');
        }
    }


    /**
     * Remove Profile picture.
     */
    public function removeProfilePicture()
    {
        try {
            UserMeta::updateOrCreate(
                ['ref_parent' => Auth::user()->id, 'meta_key' => 'profile_picture'],
                ['status' => 'inactive']
            );
            return response()->json([
            'status' => 'success',
            'message' => 'Profile picture removed successfully'
        ]);
        } catch (\Exception $e) {
            Log::error($e);
            // Handle other exceptions
            abort(500);
        }
    }
        
}
