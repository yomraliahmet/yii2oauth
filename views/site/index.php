<?php

/* @var $this yii\web\View */

$this->title = 'Yii2 OAuth';
?>
<div class="site-index">

    <?php if(Yii::$app->user->isGuest): ?>
    <div class="jumbotron">
        <h1>Facebook Giriş</h1>

        <p class="lead">Aşağıdaki butona tıklayarak sayfalarınızı ve onlara gelen mesajları görebilirsiniz.</p>

        <a class="btn btn-lg btn-outline-success" href="/site/auth?authclient=facebook"><i style="color: #0062cc" class="fab fa-facebook"></i> Facebook ile Giriş Yap</a>
    </div>
    <?php endif; ?>


    <div class="body-content">
        <?php if(isset($data) && !Yii::$app->user->isGuest): ?>
            <div class="row">
                <a href="#" class="sidebar-close-button"><i class="fa fa-bars text-dark"></i></a>
            </div>
        <?php endif; ?>
        <div class="row">
            <?php if(isset($data) && !Yii::$app->user->isGuest): ?>
            <div id="sidebar" class="col-md-4 col-sm-12 bg-light pt-2">
                <h4 class="text-danger" style="font-style: italic;">Info</h4>
                <hr>
                <div class="media">
                    <img src="<?= Yii::$app->user->getIdentity()->image ?? "" ?>?type=small" class="mr-3" alt="...">
                    <div class="media-body">
                        <h5 class="mt-0"><?= Yii::$app->user->getIdentity()->name ?? "" ?></h5>
                        <?= Yii::$app->user->getIdentity()->email ?? "" ?>
                    </div>
                </div>
                <br>
                <h4 class="text-danger" style="font-style: italic;">Pages</h4>
                <hr>
                <div class="list-group pages-list">
                    <?php foreach ($data as $pages): ?>
                        <a href="#" class="list-group-item list-group-item-action mb-1"  data-id="<?= $pages["id"] ?? "" ?>"  data-token="<?= $pages["access_token"] ?? "" ?>"><i class="fa fa-arrow-right text-danger"></i> <?= $pages["name"] ?? "" ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div id="main" class="col-md-8 col-sm-12">

                <div class="alert-content">
                    <div class="spinner-border spinner-loading" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
                <div class="card messages-card">
                    <div class="row g-0">
                        <div class="col-12 col-lg-5 col-xl-4 border-right">

                            <div class="px-4 d-none d-md-block">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <input type="text" class="form-control my-3 search-input" placeholder="Search...">
                                    </div>
                                </div>
                            </div>
                            <div class="message-users-list">
                            </div>
                            <hr class="d-block d-lg-none mt-1 mb-0">
                        </div>
                        <div class="col-12 col-lg-7 col-xl-8">
                            <div class="py-2 px-4 border-bottom d-none d-lg-block">
                                <div class="d-flex align-items-center py-1">
                                    <div class="position-relative" id="message-picture">
                                    </div>
                                    <div class="flex-grow-1 pl-1">
                                        <strong id="name"></strong>
                                    </div>
                                </div>
                            </div>

                            <div class="position-relative">
                                <div class="chat-messages p-4" style="height:600px;overflow: auto;">
                                </div>
                            </div>

                            <div class="flex-grow-0 py-3 px-4 border-top">
                                <div class="input-group">
                                    <input disabled type="text" class="form-control" placeholder="Type your message">
                                    <button disabled class="btn btn-primary">Send</button>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

    </div>
</div>


