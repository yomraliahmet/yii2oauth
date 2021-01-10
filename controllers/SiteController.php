<?php

namespace app\controllers;

use app\models\User;
use Yii;
use yii\filters\AccessControl;
use yii\httpclient\Client;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\components\AuthHandler;

class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
            'auth' => [
                'class' => 'yii\authclient\AuthAction',
                'successCallback' => [$this, 'onAuthSuccess'],
            ],
        ];
    }

    public function onAuthSuccess($client)
    {
        (new AuthHandler($client))->handle();
    }
    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        $data["data"] = [];
        if(!Yii::$app->user->isGuest){
            $sessionName = "yii\authclient\clients\Facebook_facebook_token";
            $session = Yii::$app->getSession()->get($sessionName);
            if($session){
                $authToken = $this->getAuthToken($session);
                $data["data"] = $this->getPagesAndInfos($authToken);
            }
        }

        return $this->render('index',$data);
    }

    public function actionUsers()
    {
        $formatter = \Yii::$app->formatter;

        $post = $this->request->post();
        $pageUsers = $this->getMessageUsers($post);

        $newUsers = [];
        foreach ($pageUsers as $pageUser){
            $newUsers[] = [
                "page_id" => $pageUser["id"],
                "unread_count" => $pageUser["unread_count"],
                "updated_time" => $formatter->asDatetime($pageUser["updated_time"]),
                "user_id" => $pageUser["participants"]["data"][0]["id"],
                "user_name" => $pageUser["participants"]["data"][0]["name"],
                "user_image" => $this->getUserImage([
                    "user_id" => $pageUser["participants"]["data"][0]["id"],
                    "token" => $post["token"]
                ]) ?? null,
                "token" => $post["token"]

            ];
        }

        return $this->asJson([
          "users" => $newUsers
        ]);
    }

    public function actionMessages()
    {
        $formatter = \Yii::$app->formatter;
        $post = $this->request->post();
        $messages = $this->getMessages($post);

        $newMessages = [];

        foreach ($messages as $message){
            $newMessages[] = [
                "message_id" => $message["id"],
                "message_user_id" => $message["from"]["id"],
                "name" => $message["from"]["name"],
                "message" => $message["message"],
                "created_time" => $formatter->asDatetime($message["created_time"]),
            ];
        }

        return $this->asJson([
            "messages" => $newMessages
        ]);
    }

    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goHome();
        }

        $model->password = '';
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * @param Yii\authclient\OAuthToken $oAuthToken
     * @return array
     */
    public function getAuthToken(yii\authclient\OAuthToken $oAuthToken) : array
    {
        $data = $oAuthToken->params;
        $data["oauth_user_id"] = Yii::$app->getUser()->identity->fbAuth->source_id ?? null;

        return $data;
    }

    /**
     * @param null $data
     * @return null
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\httpclient\Exception
     */
    public function getPagesAndInfos($data = null)
    {
        $session = Yii::$app->session;
        header("Content-Type:application/json, charset=UTF-8");

        if(!$data) return null;
        if($session->has("pages")) return $session->get("pages");

        $baseUrl = "https://graph.facebook.com";
        $client = new Client();
        $response = $client->createRequest()
            ->setMethod("GET")
            ->setUrl($baseUrl."/" . $data["oauth_user_id"] . "/accounts")
            ->addHeaders([
                'Content-Type' => 'application/json; charset=UTF-8',
                'Authorization' => 'Bearer ' . $data["access_token"],
            ])
            ->send();

        if ($response->isOk) {
            $pages = [];
            foreach ($response->data["data"] as $page){
                $pages[] = [
                    "id" => $page["id"],
                    "name" => $page["name"],
                    "access_token" => $page["access_token"],
                ];
            }
            $session->set('pages', $pages);
            return $pages;
        }else{
            echo $response->getStatusCode();
            exit;
        }
    }

    /**
     * @param array $data
     * @return mixed|null
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\httpclient\Exception
     */
    public function getMessageUsers(array $data)
    {
        $baseUrl = "https://graph.facebook.com";
        $client = new Client();
        $response = $client->createRequest()
            ->setMethod("GET")
            ->setUrl($baseUrl."/" . $data["page_id"] . "/conversations?fields=participants,unread_count,snippet,updated_time")
            ->addHeaders([
                'Content-Type' => 'application/json; charset=UTF-8',
                'Authorization' => 'Bearer ' . $data["token"],
            ])
            ->send();

        if ($response->isOk) {
            return $response->data["data"];
        }else{
            return null;
        }
    }

    /**
     * @param $data
     * @return mixed|null
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\httpclient\Exception
     */
    public function getUserImage($data)
    {
        $baseUrl = "https://graph.facebook.com";
        $client = new Client();
        $response = $client->createRequest()
            ->setMethod("GET")
            ->setUrl($baseUrl."/" . $data["user_id"] . "/?fields=profile_pic")
            ->addHeaders([
                'Content-Type' => 'application/json; charset=UTF-8',
                'Authorization' => 'Bearer ' . $data["token"],
            ])
            ->send();

        if ($response->isOk) {
            return $response->data["profile_pic"];
        }else{
            return null;
        }
    }

    /**
     * @param $data
     * @return mixed|null
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\httpclient\Exception
     */
    public function getMessages($data)
    {
        $baseUrl = "https://graph.facebook.com";
        $client = new Client();
        $response = $client->createRequest()
            ->setMethod("GET")
            ->setUrl($baseUrl."/" . $data["page_id"] . "/messages/?fields=id,message,created_time,from")
            ->addHeaders([
                'Content-Type' => 'application/json; charset=UTF-8',
                'Authorization' => 'Bearer ' . $data["token"],
            ])
            ->send();

        if ($response->isOk) {
            return $response->data["data"];
        }else{
            return null;
        }
    }
}
