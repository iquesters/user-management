<?php

namespace Iquesters\UserManagement\Http\Controllers;

use App\Models\MediaResources;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Iquesters\Foundation\Constants\EntityStatus;
use Iquesters\Foundation\Support\UserModel;
use Iquesters\Foundation\System\Traits\Loggable;
use Iquesters\UserManagement\Http\Controllers\Utils\MediaUtil;
use Iquesters\UserManagement\Models\UserMeta;

class MediaController extends Controller
{
    use Loggable;

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
        $user = UserModel::query()->find(Auth::user()->id);

        return view('media.library');
    }

    /**
     * Store file and create media resource object
     *
     *
     * @return $media_resource
     */
    protected function prepare_media_resource($file)
    {
        // generate a unique name for the file
        $fileName = time().'_'.$file->getClientOriginalName();
        static::logInfo('fileName='.$fileName);

        // Store the file in the 'media' directory
        $media_url = basename(MediaUtil::storeFile($file));
        static::logInfo('media_url='.$media_url);
        $filePath = $file->getRealPath();
        $media_type = MediaUtil::getMediaType($filePath);
        static::logInfo('media_type='.$media_type);

        $media_ctx = ContextController::get_context_model();

        return [
            'uid' => Str::ulid()->toString(),
            'model' => $media_ctx->model,
            'model_id' => $media_ctx->model_id,
            'media_url' => $media_url,
            'media_type' => $media_type,
            'extra_info' => '{}',
            'status' => EntityStatus::ACTIVE,
        ];
    }

    /**
     * Prepare media_resources
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
            static::logInfo('Uploading media...');
            // get the file
            $files = $request->file('media');

            if (! is_array($files)) {
                $files = [$files];
            }

            // check the file validation
            $errMsgs = [
                'media.*.required' => 'Please select a file',
                'media.*.file' => 'Please select a file',
                'media.*.max' => 'Please select a file size within 2MB',
                'media.*.mimes' => 'Please choose a jpg, jpeg or png file',
            ];
            $validation_expression = [
                'media.*' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:2048'],
            ];
            $validator = Validator::make($request->all(), $validation_expression, $errMsgs);
            if (! $validator->fails()) {
                $media_resources = $this->prepare_media_resources($files);
                $medias = UserMeta::updateOrCreate(
                    ['ref_parent' => Auth::user()->id, 'meta_key' => 'profile_picture'],
                    ['meta_value' => $media_resources[0]['media_url'], 'status' => 'active']
                );

                UserMeta::updateOrCreate(
                    ['ref_parent' => Auth::user()->id, 'meta_key' => 'profile_picture_path'],
                    ['meta_value' => 'storage/media/', 'status' => 'active']
                );
                static::logInfo('medias='.$medias);

                if (isset($medias)) {
                    return redirect()->back()->with('success', 'Profile picture updated.');
                } else {
                }
            } else {
                return redirect()->back()->withErrors($validator->errors())->withInput();
            }
        } catch (\Exception $e) {
            static::logError($e->getMessage(), ['exception' => $e]);
            // Handle other exceptions
            abort(500);
        }
    }

    /**
     * Get a newly created resource in storage
     */

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
        static::logInfo('delete status update result='.$result);
        if ($result) {
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
        static::logInfo('delete result='.$result);
        if ($result) {
            return redirect()->route($route)->with('error', 'Media deleted successfully');
        } else {
            return redirect()->back()->with('warning', 'Media delete failed.');
        }
    }

    /**
     * Remove the profile picture (the file is kept; the picture is deactivated).
     */
    public function removeProfilePicture()
    {
        $removed = UserMeta::where('ref_parent', Auth::id())
            ->where('meta_key', 'profile_picture')
            ->where('status', EntityStatus::ACTIVE)
            ->update(['status' => EntityStatus::INACTIVE]);

        $type = $removed ? 'success' : 'info';
        $message = $removed ? 'Profile picture removed.' : 'There is no profile picture to remove.';

        // Form schema "user-profile-picture-remove" (modal) submits with fetch().
        if (request()->wantsJson()) {
            session()->flash($type, $message);

            return response()->json(['success' => true, 'message' => $message, 'redirect_url' => route('myprofile', absolute: false)]);
        }

        return redirect()->route('myprofile')->with($type, $message);
    }

    /**
     * Serve an uploaded profile picture from the public disk, so pictures work
     * without `php artisan storage:link`. File names are random (Storage::putFile).
     */
    public function picture(string $filename)
    {
        $path = 'media/'.basename($filename);

        if (! preg_match('/^[A-Za-z0-9._-]+$/', $filename) || ! Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return Storage::disk('public')->response($path, null, [
            'Cache-Control' => 'private, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
