<?php

use RedBeanPHP\R as R;

class UserController extends BaseController
{
    public function login()
    {
        if (isset($_POST['username']) && isset($_POST['password']) && strlen($_POST['password']) > 2) {
            $foundBean = R::findOne('user', 'username = ? ', [$_POST['username']]);
            if ($foundBean) {
                return $this->loginPost($foundBean["id"], $_POST['username'], $_POST['password'], $foundBean['password']);
            } else {
                return "User does not exist.";
            }
        } else {
            return "Not valid parameters.";
        }
    }

    public function registerUser()
    {
        if (isset($_POST['regUsername']) && isset($_POST['regPassword']) && isset($_POST['passwordValidation']) && strlen($_POST['regUsername']) < 21) {
            $foundBean = R::findOne('user', 'username = ? ', [$_POST['regUsername']]);
            if (!$foundBean) {
                if ($_POST['regPassword'] == $_POST['passwordValidation']) {
                    $createUser = R::dispense('user');
                    $createUser->username = $_POST['regUsername'];
                    $createUser->password = crypt($_POST['regPassword'], '$1$pas$');
                    $createUser->path = 'default_pfp.svg';
                    $createUser->description = 'Welcome to my profile!';
                    R::store($createUser);
                    $this->loginPost($createUser->id, $createUser->username, $_POST['regPassword'], $createUser->password);
                } else {
                    return "Passwords do not match.";
                }
            } else {
                return "Username already exists.";
            }
        } else {
            return "Not set or invalid parameters.";
        }
    }

    public function loginPost($id, $username, $password, $hashPassword)
    {
        $foundUser = $this->getBeanById('user', $id);
        if (isset($foundUser["username"]) && isset($foundUser["password"])) {
            if ($foundUser["username"] == $username && password_verify($password, $hashPassword)) {
                $_SESSION['userId'] = $id;
                $_SESSION['username'] = $username;
                $_SESSION['path'] = $foundUser['path'];
                if (isset($foundUser['liked_posts'])) {
                    $_SESSION['liked_posts'] = $foundUser['liked_posts'];
                }
                header("Location: /");
            } else {
                return "Incorrect username and/or password";
            }
        } else {
            return "Incorrect username and/or password";
        }
    }

    public function changeProfile($userId, $alter, $content)
    {
        $user = $this->getBeanById('user', $userId);
        switch ($alter) {
            case "profilePicture":
                $user->path = $content;
                $_SESSION['path'] = $content;
                R::store($user);
                break;
            case "username":
                $newUsername = ltrim($content);
                if (strlen($newUsername) > 20) {
                    return "Username is too long. 20 characters maximum.";
                } else if (strlen($newUsername) < 4) {
                    return "Username too short. 3 characters minimum.";
                }
                $user->username = $content;
                $_SESSION['username'] = $content;
                R::store($user);
                break;
            case "description":
                if (strlen($content) > 300) {
                    return "Description is too long. No more than 300 characters allowed.";
                }
                $user->description = $content;
                R::store($user);
                break;
        }
    }

    public function changePassword($id, $curPassword, $newPassword, $newPasswordValidation)
    {
        if ($curPassword !== $newPassword) {
            if ($newPassword == $newPasswordValidation) {
                $foundUser = $this->getBeanById('user', $id);
                $hashPassword = $foundUser['password'];
                if (password_verify($curPassword, $hashPassword)) {
                    $foundUser->password = crypt($newPassword, '$1$pas');
                    R::store($foundUser);
                } else {
                    return "Invalid password";
                }
            } else {
                return "Passwords do not match";
            }
        } else {
            return "Can not use the same password.";
        }
    }

    public function uploadFile()
    {
        $target_dir = "../img/";
        $fileName = basename($_FILES["fileToUpload"]["name"]);
        $newName = uniqid();
        $newName .= ".png";
        $fileName = $newName;
        $target_file = $target_dir . $fileName;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Check if image file is a actual image or fake image
        if (isset($_POST["submit"])) {
            $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
            if ($check !== false) {
                // Upload is an image
                $uploadOk = 1;
            } else {
                $uploadOk = 0;
                $error = "Upload is not an image.";
            }
        }
        
        // Check if file already exists
        if (file_exists($target_file)) {
            $uploadOk = 0;
            $error = "File already exists.";
        }
        
        // Check file size
        if ($_FILES["fileToUpload"]["size"] > 500000) {
            $uploadOk = 0;
            $error = "File too large.";
        }
        
        // Allow certain file formats
        if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
            $uploadOk = 0;
            $error = "Upload is not correct format.";
        }
        
        // Check if $uploadOk is set to 0 by an error
        if ($uploadOk == 0) {
            return $error;
        // if everything is ok, try to upload file
        } else {
            if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
                $this->changeProfile($_SESSION['userId'], 'profilePicture', $fileName);
            } else {
                return "Your file was not uploaded.";
            }
        }
    }
}