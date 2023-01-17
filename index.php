<?php
require 'DotEnv.php';
require 'url_join.php';
(new DotEnv)->load();
session_start();

class AuthStates {
        const BEFORE_AUTH = 1;
        const AUTH_IN_PROGRESS = 2;
        const AFTER_AUTH = 3;
}

if (!isset($_SESSION['state']))
        $_SESSION['state'] = AuthStates::BEFORE_AUTH;

try {
        $oauth = new OAuth($_ENV['CONSUMER_KEY'], $_ENV['CONSUMER_SECRET'], OAUTH_SIG_METHOD_HMACSHA1, OAUTH_AUTH_TYPE_URI);
        if ($_SESSION['state'] == AuthStates::BEFORE_AUTH) {
                $request_token_info = $oauth->getRequestToken(url_join([$_ENV['USOS_API_BASEURL'], 'services/oauth/request_token?scopes=studies']), $_ENV['APP_URL']);
                $_SESSION['secret'] = $request_token_info['oauth_token_secret'];
                $_SESSION['state'] = AuthStates::AUTH_IN_PROGRESS;
                header('Location: '.url_join([$_ENV['USOS_API_BASEURL'],'services/oauth/authorize?oauth_token='.$request_token_info['oauth_token']]));
        }
        else if ($_SESSION['state'] == AuthStates::AUTH_IN_PROGRESS) {
                if (!isset($_GET['oauth_token'])) {
                        print "Nie jesteś zalogowany";
                        exit;
                }
                $oauth->setToken($_GET['oauth_token'], $_SESSION['secret']);
                $access_token_info = $oauth->getAccessToken(url_join([$_ENV['USOS_API_BASEURL'], 'services/oauth/access_token']));
                $_SESSION['state'] = AuthStates::AFTER_AUTH;
                $_SESSION['token'] = $access_token_info['oauth_token'];
                $_SESSION['secret'] = $access_token_info['oauth_token_secret'];
                header('Location: '.$_ENV['APP_URL']);
        }
        else if ($_SESSION['state'] == AuthStates::AFTER_AUTH) {
                $oauth->setToken($_SESSION['token'], $_SESSION['secret']);
                $oauth->fetch(url_join([$_ENV['USOS_API_BASEURL'], "services/users/user?fields=first_name|last_name|sex|student_number"]));
                $json = json_decode($oauth->getLastResponse());
                print "<pre>".json_encode($json, JSON_PRETTY_PRINT)."</pre>";
                exit;
        }
} catch(OAuthException $E) {
        session_destroy();
        print "OAuth Error";
}
