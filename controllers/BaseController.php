<?php

use RedBeanPHP\R as R;

class BaseController
{
    public function __construct()
    {
        if (!R::testConnection()) {
            R::setup('mysql:host=localhost;
            dbname=binsta', 'bit_academy', 'bit_academy');
        }
    }

    public function authorizeUser()
    {
        if (isset($_SESSION['userId'])) {
            return;
        } else {
            header("Location: /user/login");
        }
    }

    public function getBeanById($typeOfBean, $beanId)
    {
        $validTypes = ['user', 'post'];
        if (in_array($typeOfBean, $validTypes)) {
            return R::findOne($typeOfBean, " id = ? ", [$beanId]);
        } else {
            displayError("Bean not found", 'error');
        }
    }

    public function setPath($path = null, $location = null)
    {
        if ($location == 'home') {
            header("Location: /");
        } elseif ($path !== null) {
            header("Location: " . $path);
        }
    }

    public function redirectUserProfile($location)
    {
        if ($location == 'home') {
            header("Location: /");
        } else {
            if (isset($_GET['view']) && $_GET['view'] == 'liked') {
                header("Location: /user/profile?id=$location&view=liked");
            } else {
                header("Location: /user/profile?id=$location");
            }
        }
    }

    public function showAll($typeOfBean, $limit = null)
    {
        $validTypes = ['user', 'post', 'comment'];
        if (in_array($typeOfBean, $validTypes)) {
            if ($limit != null) {
                return R::findAll($typeOfBean, " ORDER BY id DESC LIMIT $limit ");
            }
            return R::findAll($typeOfBean, "ORDER BY id DESC");
        } else {
            exit(error('404', 'Not found'));
        }
    }

    public function findInDb($typeOfBean, $query)
    {
        return R::find($typeOfBean, 'username LIKE ? LIMIT 50', ["$query%"]);
    }
}