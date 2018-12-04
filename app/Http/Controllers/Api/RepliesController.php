<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\ReplyRequest;
use App\Models\Reply;
use App\Models\Topic;
use App\Models\User;
use App\Transformers\ReplyTransformer;
use Illuminate\Http\Request;

class RepliesController extends Controller
{

    /**
     * 话题回复列表
     * @param Topic $topic
     * @return \Dingo\Api\Http\Response
     */
    public function index(Topic $topic)
    {
        $replies = $topic->replies()->paginate(20);
        return $this->response->paginator($replies , new ReplyTransformer());
    }

    /**
     * 获取某个用户的回复列表
     * @param User $user
     * @return \Dingo\Api\Http\Response
     */
    public function userIndex(User $user)
    {
        $replies = $user->replies()->paginate(20);

        return $this->response->paginator($replies, new ReplyTransformer());
    }
    
    /**
     * 发布回复
     * @param ReplyRequest $request
     * @param Topic $topic
     * @param Reply $reply
     * @return $this
     */
    public function store(ReplyRequest $request , Topic $topic , Reply $reply)
    {
        $reply->content = $request->content;
        $reply->topic_id = $topic->id;
        $reply->user_id = $this->user()->id;
        $reply->save();

        return $this->response->item($reply, new ReplyTransformer())
                ->setStatusCode(201);
    }

    /**
     * 删除某条回复
     * @param Topic $topic
     * @param Reply $reply
     * @return \Dingo\Api\Http\Response|void
     * @throws \Exception
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(Topic $topic , Reply $reply)
    {
        if ($topic->id != $reply->topic_id){
            return $this->response->errorBadRequest();
        }

        $this->authorize('destroy',$reply);

        $reply->delete();

        return $this->response->noContent();
    }
}
