<?php

if (!isset($_SESSION)) {
    session_start();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../vendor/autoload.php';

if (isset($_GET['url'])) {
    $url = $_GET['url'];
    $params = explode("/", $url);
}

if (isset($params[0]) && strlen($params[0]) > 1) {
    $cInput = $params[0];
} else {
    $cInput = 'home';
}

if (isset($params[1])) {
    $mInput = $params[1];
} else {
    $mInput = 'index';
}

$message = "";
$error = "";
$viewPath = $cInput . "/" . $mInput . ".twig";
$location;


if (file_exists("../views/" . $viewPath)) {
    // Checks if a user is logged in, if true user can not visit login or register page.
    if (isset($_SESSION['userId']) && $cInput == 'user') {
        if ($mInput !== 'profile') {
            if ($mInput !== 'edit' && $mInput !== 'login' && $mInput !== 'register') {
                header("Location: /");
            }
        }
    }

    // Checks what button was pressed and handles it accordingly.
    if (isset($_POST['submit'])) {
        switch ($_POST['submit']) {
            case "userLogin":
                $user = new UserController();
                $error = $user->login();
                if (strlen($error) < 2) {
                    header("Location: /");
                }
                break;
            case "userRegister":
                $user = new UserController();
                $error = $user->registerUser();
                if (strlen($error) > 1) {
                    displayError($error, 'error');
                }
                break;
            case "createPost":
                    $post = new PostController();
                    $post->authorizeUser();
                    $error = $post->createPost($_POST['codeContent'], $_POST['description'], $_POST['codeLanguage']);
                    header("Location: /");
                break;
            case "postComment":
                $post = new PostController();
                $post->authorizeUser();
                $post->postComment($_SESSION['userId'], $_POST['commentPostId'], $_POST['commentContent']);

                $location = $_SESSION['location'];
                if ($location == 'home') {
                    $post->redirectUserProfile('home');
                } else {
                    $post->redirectUserProfile($location);
                }
                break;
            case "likePost":
                $post = new PostController();
                $post->authorizeUser();
                $post->likePost($_SESSION['userId'], $_POST['likePostId']);

                $location = $_SESSION['location'];
                if ($location == 'home') {
                    $post->redirectUserProfile('home');
                } else {
                    $post->redirectUserProfile($location);
                }
                break;
            case "unlikePost":
                $post = new PostController();
                $post->authorizeUser();
                $post->unlikePost($_SESSION['userId'], $_POST['unlikePostId']);

                $location = $_SESSION['location'];
                if ($location == 'home') {
                    $post->redirectUserProfile('home');
                } else {
                    $post->redirectUserProfile($location);
                }
                break;
            case "changeProfile":
                $user = new UserController();
                $user->authorizeUser();
                $userId = $_SESSION['userId'];
                $foundUser = $user->getBeanById('user', $userId);
                $allUsers = $user->showAll('user');
                $newUsername = str_replace(' ', '', $_POST['newUsername']);
                $newUsername = strtolower($newUsername);

                if (isset($_FILES["fileToUpload"]) && strlen($_FILES['fileToUpload']['name']) > 0) {
                    $error = $user->uploadFile();
                }
                
                foreach ($allUsers as $singleUser) {
                    $singleUsername = strtolower($singleUser['username']);
                    if ($singleUsername == $newUsername && $newUsername !== $foundUser['username']) {
                        $error = "Username already exists.";
                    }
                }
                if ($newUsername !== $foundUser['username'] && strlen($error) < 2) {
                    $message = $user->changeProfile($userId, 'username', $newUsername);
                }
                if ($_POST['newDescription'] !== $foundUser['description']) {
                    $message = $user->changeProfile($userId, 'description', $_POST['newDescription']);
                }
                if (empty($error) && empty($message)) {
                    header("Location: /user/profile?id=$userId");
                }
                break;
            case "changePassword":
                $user = new UserController();
                $user->authorizeUser();
                $error = $user->changePassword($_SESSION['userId'], $_POST['curPassword'], $_POST['newPassword'], $_POST['newPasswordValidation']);
                if (strlen($error) < 2) {
                    $userId = $_SESSION['userId'];
                    header("Location: /user/profile?id=$userId");
                }
                break;
            case "logout":
                unset($_SESSION);
                unset($_POST);
                session_destroy();
                header("Location: /user/login");
                break;
        }
    }

    switch ($cInput) {
        case "home":
            $_SESSION['location'] = 'home';
            if (!isset($_POST['submit'])) {
                $post = new PostController();
                $post->authorizeUser();
                loadTemp('../views/', $viewPath, ['posts' => $post->showAll('post'), 'comments' => $post->showAll('comment'), 'likes' => $post->getUserLikedIds($_SESSION['userId'])]);
            } else {
                loadTemp('../views/', $viewPath);
            }
            break;
        case "user":
            if ($mInput == 'profile') {
                $post = new PostController();
                $post->authorizeUser();
                if (isset($_GET['id'])) {
                    $id = $_GET['id'];
                    $_SESSION['location'] = $id;
                    if (isset($_GET['view']) && $_GET['view'] == 'liked') {
                        $foundUser = $post->getBeanById('user', $id);
                        if (empty($foundUser['liked_posts'])) {
                            $message = "This user has not liked anything...";
                            loadTemp('../views/', $viewPath, ['user' => $post->getBeanById('user', $id)]);
                        } else {
                            loadTemp('../views/', $viewPath, ['user' => $post->getBeanById('user', $id), 'posts' => $post->getUserLikedPosts($_GET['id']), 
                            'comments' => $post->showAll('comment'), 'likes' => $post->getUserLikedIds($_SESSION['userId'])]);
                        }
                    } else {
                        $foundPosts = $post->getPosts($id);
                        if (empty($foundPosts)) {
                            $message = "This user has not posted anything...";
                        }
                        loadTemp('../views/', $viewPath, ['user' => $post->getBeanById('user', $id), 'posts' => $post->getPosts($_GET['id']), 
                        'comments' => $post->showAll('comment'), 'likes' => $post->getUserLikedIds($_SESSION['userId'])]);
                    }
                } else {
                    header("Location: /");
                }
            } else if ($mInput == 'edit') {
                $post = new PostController();
                $post->authorizeUser();
                loadTemp('../views/', $viewPath, ['user' => $post->getBeanById('user', $_SESSION['userId'])]);
            } else {
                loadTemp('../views/', $viewPath);
            }
            break;
        case "search":
            $q = str_replace(' ', '', $_GET['q']);
            if ($mInput == 'result' && isset($q) && strlen($q) > 0) {
                $base = new BaseController();
                $_SESSION['q'] = $q;
                $foundInDb = $base->findInDb('user', $q);
                if (empty($foundInDb)) {
                    $message = "No results found.";
                }
                loadTemp('../views/', $viewPath, ['results' => $foundInDb]);
            } else {
                header("Location: /");
            }
            break;
        default:
            $post = new PostController();
            $post->authorizeUser();
            loadTemp('../views/', $viewPath, ['user' => $post->getBeanById('user', $_SESSION['userId'])]);
            break;
    }

    if (strlen($error) > 1) {
        displayError($error, 'error');
    } else if (strlen($message) > 1) {
        displayError($message, 'message');
    }

    if (strlen($message) > 1) {
    }
} else {
    error('404', 'This page is not available.');
}