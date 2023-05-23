<?php

require_once './vendor/autoload.php';
use RedBeanPHP\R as R;

R::setup('mysql:host=localhost;
dbname=binsta', 'bit_academy', 'bit_academy');

R::nuke();

$users = [
        [
            "username" => "admin",
            "description" => "Welkom op mijn profiel! Ik ben een admin van deze website!",
            "path" => "aapske.png",
            "likedPosts" => "",
            "password" => "admin"
        ],
        [
            "username" => "dylan",
            "description" => "Ik ben dylan",
            "path" => "f1car.png",
            "likedPosts" => "",
            "password" => "kuipers"
        ]
    ];

$code = <<<'LABEL'
    $validTypes = ['user', 'post'];
    if (in_array($typeOfBean, $validTypes)) {
        return R::findOne($typeOfBean, " id = ? ", [$beanId]));
    } else {
        $error = "Not a valid bean";
    }
LABEL;

$posts = [
        [
            "id" => 1,
            "code" => $code,
            "description" => "This is my code",
            "language" => "PHP",
            "likes" => 0
        ],
        [
            "id" => 2,
            "code" => $code,
            "description" => "Look at this!",
            "language" => "PHP",
            "likes" => 0
        ]
    ];

$comments = [
        [
            "id" => 1,
            "postedById" => "dylan",
            "content" => "Nice code!"
        ],
        [
            "id" => 2,
            "postedById" => "admin",
            "content" => "This looks great, I should try this myself!"
        ]
    ];

foreach ($users as $userItem) {
    //Create a user table
    $createUser = R::dispense('user');
    $createUser->username = $userItem["username"];
    $createUser->path = $userItem["path"];
    $createUser->likedPosts = $userItem["likedPosts"];
    $createUser->description = $userItem["description"];
    $createUser->password = crypt($userItem["password"], '$1$pas');
    //Store the user in the database
    R::store($createUser);
    foreach ($posts as $postItem) {
        //Request id of just inserted user
        if ($createUser->id == $postItem["id"]) {
            // Create posts for a specific user
            $post = R::dispense('post');
            $post->code = $postItem["code"];
            $post->description = $postItem["description"];
            $post->language = $postItem["language"];
            $post->likes = $postItem["likes"];
            // Creates comments
            foreach ($comments as $commentItem) {
                if ($commentItem["id"] == $postItem["id"]) {
                    $comment = R::dispense('comment');
                    $comment->content = $commentItem['content'];
                    $createUser->ownCommentList[] = $comment;
                    $post->ownPostList[] = $comment;
                }
            }
            // Add post to the specified user
            $createUser->ownPostList[] = $post;
            // Store the user once again
            R::store($createUser);
        }
    }    
}
// Adds comments to each post
foreach ($posts as $post) {
    foreach ($comments as $comment) {
        $user = R::findOne('user', 'username = ?', [$comment['postedById']]);
        $commentDB = R::findOne('comment', 'content = ?', [$comment['content']]);
        $user->ownCommentList[] = $commentDB;
        R::store($user);
    }
}