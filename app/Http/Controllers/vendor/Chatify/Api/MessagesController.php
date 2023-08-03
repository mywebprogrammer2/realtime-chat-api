<?php

namespace App\Http\Controllers\vendor\Chatify\Api;

use App\Facades\ReusableFacades;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Response;
use App\Models\ChMessage as Message;
use App\Models\ChFavorite as Favorite;
use App\Facades\ChatifyCustom as Chatify;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class MessagesController extends Controller
{
    protected $perPage = 30;

     /**
     * Authinticate the connection for pusher
     *
     * @param Request $request
     * @return void
     */
    public function pusherAuth(Request $request)
    {

        return json_decode( Chatify::pusherAuth(
            $request->user(),
            Auth::user(),
            $request['channel_name'],
            $request['socket_id']
        ));
    }

    /**
     * Fetch data by id for (user/group)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function idFetchData(Request $request)
    {
        return auth()->user();
        // Favorite
        $favorite = Chatify::inFavorite($request['id']);

        // User data
        if ($request['type'] == 'user') {
            $fetch = User::where('id', $request['id'])->first();
            if($fetch){
                $userAvatar = Chatify::getUserWithAvatar($fetch)->avatar;
            }
        }

        // send the response
        return Response::json([
            'favorite' => $favorite,
            'fetch' => $fetch ?? null,
            'user_avatar' => $userAvatar ?? null,
        ]);
    }

    /**
     * This method to make a links for the attachments
     * to be downloadable.
     *
     * @param string $fileName
     * @return \Illuminate\Http\JsonResponse
     */
    public function download($fileName)
    {
        $path = config('chatify.attachments.folder') . '/' . $fileName;
        if (Chatify::storage()->exists($path)) {
            return response()->json([
                'file_name' => $fileName,
                'download_path' => Chatify::storage()->url($path)
            ], 200);
        } else {
            return response()->json([
                'message'=>"Sorry, File does not exist in our server or may have been deleted!"
            ], 404);
        }
    }

    /**
     * Send a message to database
     *
     * @param Request $request
     * @return JSON response
     */
    public function send(Request $request)
    {
        // default variables
        $error = (object)[
            'status' => 0,
            'message' => null
        ];
        $attachment = null;
        $attachment_title = null;

        // if there is attachment [file]
        if ($request->hasFile('file')) {
            // allowed extensions
            $allowed_images = Chatify::getAllowedImages();
            $allowed_files  = Chatify::getAllowedFiles();
            $allowed        = array_merge($allowed_images, $allowed_files);

            $file = $request->file('file');
            // check file size
            if ($file->getSize() < Chatify::getMaxUploadSize()) {
                if (in_array(strtolower($file->extension()), $allowed)) {
                    // get attachment name
                    $attachment_title = $file->getClientOriginalName();
                    // upload attachment and store the new name
                    $attachment = Str::uuid() . "." . $file->extension();
                    $file->storeAs(config('chatify.attachments.folder'), $attachment, config('chatify.storage_disk_name'));
                } else {
                    $error->status = 1;
                    $error->message = "File extension not allowed!";
                }
            } else {
                $error->status = 1;
                $error->message = "File size you are trying to upload is too large!";
            }
        }

        if (!$error->status) {
            // send to database
            $message = Chatify::newMessage([
                'type' => $request['type'],
                'from_id' => Auth::user()->id,
                'to_id' => $request['id'],
                'body' => htmlentities(trim($request['message']), ENT_QUOTES, 'UTF-8'),
                'attachment' => ($attachment) ? json_encode((object)[
                    'new_name' => $attachment,
                    'old_name' => htmlentities(trim($attachment_title), ENT_QUOTES, 'UTF-8'),
                ]) : null,
            ]);

            // fetch message to send it with the response
            $messageData = Chatify::parseMessage($message);
            $messageData['seen']= false;
            // send to user using pusher
            if (Auth::user()->id != $request['id']) {
                $receiverMessageData = $messageData;
                $receiverMessageData['isSender'] = false;

                Chatify::push("private-chatify.".$request['id'], 'messaging', [
                    'from_id' => Auth::user()->id,
                    'to_id' => $request['id'],
                    'message' => $receiverMessageData
                ]);
            }
        }

        // send the response
        return ReusableFacades::createResponse($error->status > 0 ? false : true,[
            'message' => $messageData ?? [],
            'tempID' => $request['temporaryMsgId'],
        ],$error->message?? 'Message sent successfully.',[],$error->status > 0 ? 400 : 200);
    }

    /**
     * fetch [user/group] messages from database
     *
     * @param Request $request
     * @return JSON response
     */
    public function fetch(Request $request)
    {
        $query = Chatify::fetchMessagesQuery($request['id'])->latest();
        $messages = $query->paginate($request->per_page ?? $this->perPage);
        $totalMessages = $messages->total();
        $lastPage = $messages->lastPage();
        $response = [
            'total' => $totalMessages,
            'last_page' => $lastPage,
            'last_message_id' => collect($messages->items())->last()->id ?? null,
            'messages' => $messages->items(),
        ];
        $messageTransformed = new Collection();
        foreach ($messages->reverse() as $message) {
            $messageTransformed->push(Chatify::parseMessage($message));
        }
        $response['messages'] = $messageTransformed;

        return ReusableFacades::createResponse(true,$response,'Messages',[],200);
    }

    /**
     * Make messages as seen
     *
     * @param Request $request
     * @return void
     */
    public function seen(Request $request)
    {
        // make as seen
        $seen = Chatify::makeSeen($request['id']);
        // send the response
        return Response::json([
            'status' => $seen,
        ], 200);
    }

    /**
     * Get contacts list
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse response
     */
    public function getContacts(Request $request)
    {
        // get all users that received/sent message from/to [Auth user]
        $users = Message::rightJoin('users',  function ($join) {
            $join->on('ch_messages.from_id', '=', 'users.id')
                ->orOn('ch_messages.to_id', '=', 'users.id');
        })
        ->where('users.status',1)
        ->where('users.id','!=',Auth::user()->id)
        ->select('users.id','users.name','users.email','users.status','users.active_status','users.avatar','users.dark_mode','messenger_color',DB::raw('MAX(ch_messages.created_at) max_created_at'))
        ->orderBy('max_created_at', 'desc')
        ->groupBy('users.id')
        ->get();
        $userIds = $users->pluck('id')->flatten()->toArray();

        $latestMessages = Message::select(DB::raw('MAX(created_at) as max_created_at'))
        ->whereIn('from_id', $userIds)
        ->orWhereIn('to_id', $userIds)
        ->groupBy(DB::raw('IF(from_id IN (' . implode(',', $userIds) . '), from_id, to_id)'))
        ->get();

        $unSeenMessage = Message::where('to_id', Auth::user()->id)
        ->where('seen',0)
        ->select(DB::raw('COUNT(*) as unseen_count, from_id, to_id'))
        ->groupBy('from_id')
        ->get();

        $latestMessageIds = $latestMessages->pluck('max_created_at');

        $messages = Message::
        where(function ($q) use($userIds){
            $q->whereIn('from_id',$userIds)->where('to_id', Auth::user()->id);
        })
        ->orWhere(function ($q) use($userIds){
            $q->whereIn('to_id',$userIds)->where('from_id',Auth::user()->id);
        })
        ->whereIn('created_at', $latestMessageIds)
        ->get();

        foreach ($users as $key => $user) {
            $users[$key]['avatar'] = Chatify::getUserAvatarUrl( $users[$key]['avatar'] );
            $users[$key]['unseen'] = $unSeenMessage->where('from_id',$user->id)->first()->unseen_count ?? 0;
            $users[$key]['last_message'] = Chatify::parseMessage($messages->filter(function ($item) use($users, $key){
                return $item->from_id == $users[$key]['id'] || $item->to_id == $users[$key]['id'] ;
            })->sortByDesc('created_at')->first());
        }


        return ReusableFacades::createResponse(true,$users,'Contacts list',[],200);
    }

    /**
     * Get a single contact
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse response
     */
    public function getContact(Request $request){

        $user_id = $request->id;
        $latestMessages = Message::where(function ($q) use($user_id){
            $q->where('from_id',$user_id)->orWhere('to_id',Auth::user()->id);
        })
        ->orWhere(function ($q) use($user_id){
            $q->where('from_id',Auth::user()->id)->orWhere('to_id',$user_id);
        })
        ->orderByDesc('created_at')
        ->first();

        $unSeenMessage = Message::where('to_id', Auth::user()->id)
        ->where('from_id',$user_id)
        ->where('seen',0)
        ->select(DB::raw('COUNT(*) as unseen_count, from_id, to_id'))
        ->groupBy('from_id')
        ->first();

        $user = User::findOrFail($request->id)->toArray();
        $user['last_message'] =  Chatify::parseMessage($latestMessages);
        $user['unseen'] =  $unSeenMessage->unseen_count ?? 0;

        return ReusableFacades::createResponse(true,$user,'Get Contact',[],200);

    }

    /**
     * Put a user in the favorites list
     *
     * @param Request $request
     * @return void
     */
    public function favorite(Request $request)
    {
        $userId = $request['user_id'];
        // check action [star/unstar]
        $favoriteStatus = Chatify::inFavorite($userId) ? 0 : 1;
        Chatify::makeInFavorite($userId, $favoriteStatus);

        // send the response
        return Response::json([
            'status' => @$favoriteStatus,
        ], 200);
    }

    /**
     * Get favorites list
     *
     * @param Request $request
     * @return void
     */
    public function getFavorites(Request $request)
    {
        $favorites = Favorite::where('user_id', Auth::user()->id)->get();
        foreach ($favorites as $favorite) {
            $favorite->user = User::where('id', $favorite->favorite_id)->first();
        }
        return Response::json([
            'total' => count($favorites),
            'favorites' => $favorites ?? [],
        ], 200);
    }

    /**
     * Search in messenger
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $input = trim(filter_var($request['input']));
        $records = User::where('id','!=',Auth::user()->id)
                    ->where('name', 'LIKE', "%{$input}%")
                    ->paginate($request->per_page ?? $this->perPage);

        foreach ($records->items() as $index => $record) {
            $records[$index] += Chatify::getUserWithAvatar($record);
        }

        return Response::json([
            'records' => $records->items(),
            'total' => $records->total(),
            'last_page' => $records->lastPage()
        ], 200);
    }

    /**
     * Get shared photos
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sharedPhotos(Request $request)
    {
        $images = Chatify::getSharedPhotos($request['user_id']);

        foreach ($images as $image) {
            $image = asset(config('chatify.attachments.folder') . $image);
        }
        // send the response
        return Response::json([
            'shared' => $images ?? [],
        ], 200);
    }

    /**
     * Delete conversation
     *
     * @param Request $request
     * @return void
     */
    public function deleteConversation(Request $request)
    {
        // delete
        $delete = Chatify::deleteConversation($request['id']);

        // send the response
        return ReusableFacades::createResponse($delete ? true : false,[], $delete ? 'Deleted Successfully.' : 'Deleted failed.', [],$delete? 200: 400);
    }

    public function updateSettings(Request $request)
    {
        $msg = null;
        $error = $success = 0;

        // dark mode
        if ($request['dark_mode']) {
            $request['dark_mode'] == "dark"
                ? User::where('id', Auth::user()->id)->update(['dark_mode' => 1])  // Make Dark
                : User::where('id', Auth::user()->id)->update(['dark_mode' => 0]); // Make Light
        }

        // If messenger color selected
        if ($request['messengerColor']) {
            $messenger_color = trim(filter_var($request['messengerColor']));
            User::where('id', Auth::user()->id)
                ->update(['messenger_color' => $messenger_color]);
        }
        // if there is a [file]
        if ($request->hasFile('avatar')) {
            // allowed extensions
            $allowed_images = Chatify::getAllowedImages();

            $file = $request->file('avatar');
            // check file size
            if ($file->getSize() < Chatify::getMaxUploadSize()) {
                if (in_array(strtolower($file->extension()), $allowed_images)) {
                    // delete the older one
                    if (Auth::user()->avatar != config('chatify.user_avatar.default')) {
                        $path = Chatify::getUserAvatarUrl(Auth::user()->avatar);
                        if (Chatify::storage()->exists($path)) {
                            Chatify::storage()->delete($path);
                        }
                    }
                    // upload
                    $avatar = Str::uuid() . "." . $file->extension();
                    $update = User::where('id', Auth::user()->id)->update(['avatar' => $avatar]);
                    $file->storeAs(config('chatify.user_avatar.folder'), $avatar, config('chatify.storage_disk_name'));
                    $success = $update ? 1 : 0;
                } else {
                    $msg = "File extension not allowed!";
                    $error = 1;
                }
            } else {
                $msg = "File size you are trying to upload is too large!";
                $error = 1;
            }
        }

        $user = Auth::user();
        $user =$user->refresh() ;

        // send the response
        return ReusableFacades::createResponse(true,[
            'user'=> $user->load(['user_detail','roles','permissions'])->append('all_permissions')
        ],$error ? $msg : '', [], $error ? 400 :200);
    }

    /**
     * Set user's active status
     *
     * @param Request $request
     * @return void
     */
    public function setActiveStatus(Request $request)
    {
        $activeStatus = $request['status'] > 0 ? 1 : 0;
        $status = User::where('id', Auth::user()->id)->update(['active_status' => $activeStatus]);
        return Response::json([
            'status' => $status,
        ], 200);
    }

    /**
     * Delete message
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteMessage(Request $request)
    {
        // delete
        $delete = Chatify::deleteMessage($request['id']);

        // send the response
        return ReusableFacades::createResponse(true,
        [
            'deleted' => $delete ? 1 : 0,
        ],'Message deleted.',[],200);
    }
}
