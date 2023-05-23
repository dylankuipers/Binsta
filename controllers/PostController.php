<?php

use RedBeanPHP\R as R;

class PostController extends BaseController
{
    public function createPost($codeContent, $description, $language)
    {
        $codeCheck = ltrim($_POST['codeContent']);
        $descriptionCheck = ltrim($_POST['description']);
        if (!empty($codeCheck) && !empty($descriptionCheck)) {
            if (strlen($codeCheck) > 2000) {
                return "Code may not be longer than 2000 characters.";
            } else if (strlen($descriptionCheck) > 500) {
                return "Description may not be longer than 500 characters";
            } else {
                $post = R::dispense('post');
                $post->code = $codeContent;
                $post->description = $description;
                $post->user_id = $_SESSION['userId'];
                $post->likes = 0;
                $post->language = $language;
                R::store($post);
            }
        } else {
            return "Content and/or description may not be empty";
        }
    }

    public function postComment($userId, $postId, $content)
    {
        if (strlen($_POST['commentContent'] > 0)) {
            $comment = R::dispense('comment');
            $comment->content = $content;
            $comment->user_id = $userId;
            $comment->post_id = $postId;
            R::store($comment);
        } else {
            return "Content may not be empty";
        }
    }

    public function likePost($userId, $postId)
    {
        $user = $this->getBeanById('user', $userId);
        $post = $this->getBeanById('post', $postId);
        if (!empty($user['liked_posts'])) {
            $likedPosts = explode(',', $user['liked_posts'], -1);
            if (!in_array($postId, $likedPosts)) {
                $newList = $user['likedPosts'] .= $postId . ",";
                $user->liked_posts = $newList;
                $post->likes++;
                R::store($user);
                R::store($post);
            }
        } else {
            $user = $this->getBeanById('user', $userId);
            $user->liked_posts = $postId . ',';
            $post->likes++;
            R::store($user);
            R::store($post);
        }
    }

    public function unlikePost($userId, $postId)
    {
        $user = $this->getBeanById('user', $userId);
        $post = $this->getBeanById('post', $postId);
        $likedPosts = explode(',', $user['liked_posts'], -1);
        $newList = "";
        foreach ($likedPosts as $liked) {
            if ($postId !== $liked) {
                $newList .= $liked . ",";
            }
        }
        $user->liked_posts = $newList;
        R::store($user);
        $post->likes--;
        R::store($post);
    }

    public function getAllComments($postId)
    {
        return R::findAll('comment', 'post_id = ?', [$postId]);
    }

    public function getUserLikedIds($userId)
    {
        $user = $this->getBeanById('user', $userId);
        $likedPosts = [];

        if (!empty($user['liked_posts'])) {
            $likedPosts = explode(",", $user['liked_posts'], -1);
        }
        return $likedPosts;
    }

    public function getUserLikedPosts($userId)
    {
        $likedPosts = $this->getUserLikedIds($userId);
        return R::find('post', 'id IN (' . R::genSlots($likedPosts) . ')', $likedPosts);
    }
    
    public function getPosts($userId, $limit = null)
    {
        if ($limit != null) {
            return R::findAll('post', "user_id = ? ORDER BY id DESC LIMIT $limit ", [$userId]);
        }
        return R::findAll('post', 'user_id = ? ORDER BY id DESC', [$userId]);
    }
}