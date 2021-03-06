<?php

namespace humhub\modules\webshell;

use Yii;
use yii\base\Action;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class Module extends \humhub\components\Module
{
    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'humhub\modules\webshell\controllers';

    /**
     * @var string console greetings
     */
    public $greetings = 'Yii 2.0 Webshell';

    /**
     * @var array URL to use for `quit` command. If not set, `quit` command will do nothing.
     */
    public $quitUrl = ['/dashboard'];

    /**
     * @var string path to `yii` script
     */
    public $yiiScript = '@app/yii';
    /**
     * @var array the list of IPs that are allowed to access this module.
     * Each array element represents a single IP filter which can be either an IP address
     * or an address with wildcard (e.g. 192.168.0.*) to represent a network segment.
     * The default value is `['127.0.0.1', '::1']`, which means the module can only be accessed
     * by localhost.
     */
    public $allowedIPs = ['127.0.0.1', '::1'];

    public $checkAccessCallback;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        set_time_limit(0);
        Yii::$app->getResponse()->format = Response::FORMAT_HTML;
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        if (Yii::$app instanceof \yii\web\Application && !$this->checkAccess($action)) {
            throw new ForbiddenHttpException('You are not allowed to access this page.');
        }
        return true;
    }

    /**
     * @return boolean whether the module can be accessed by the current user
     */
    protected function checkAccess(Action $action)
    {
        $allowed = false;
        $ip = Yii::$app->getRequest()->getUserIP();
        foreach ($this->allowedIPs as $filter) {
            if ($filter === '*' || $filter === $ip || (($pos = strpos($filter, '*')) !== false && !strncmp($ip, $filter, $pos))) {
                $allowed = true;
                break;
            }
        }
        if ($allowed === false) {
            Yii::warning('Access to web shell is denied due to IP address restriction. The requested IP is ' . $ip, __METHOD__);
            return false;
        }
        if ($this->checkAccessCallback !== null && call_user_func_array($this->checkAccessCallback, [$action]) !== true) {
            Yii::warning('Access to web shell is denied due to checkAccessCallback.', __METHOD__);
            return false;
        }
        return true;
    }
}
