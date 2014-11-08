<?php

App::before(function($request){
    $preg = '/^\/login\/callback|\/logout/';
    $check =  GithubLogin::check();
    if (!$check && preg_match($preg, $request->getRequestUri()) == 0) {
        return Redirect::to(GithubLogin::authorizeUrl());

    } else if($check) {
        $user = GithubLogin::getLoginUser();
        View::share('login', $user->login);
    }
});

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/

Route::get('/logout', function(){
    $cookie = GithubLogin::logout();
    return Response::make('<center>logout success</center>')->withCookie($cookie);
});

Route::get('/login/callback', function(){
    if (GithubLogin::check()) {
        return Redirect::to('/');
    }

    $code = Input::get('code');
    if ($code == '') {
        return 'CODE ERROR';
    }

    $accessToken = \Eleme\Github\GithubAuthorize::accessToken($code);
    $client = new \Eleme\Github\GithubClient($accessToken);
    $teams = $client->request('user/teams');
    $haveEleme = false;
    $orgTeams = array();
    foreach ($teams as $team) {
        if ($team->organization->login == Config::get('github.organization')) {
            $haveEleme = true;
            $orgTeams[] = new GithubTeam($team);
        }
    }

    if ($haveEleme) {
        $user = $client->request('user');
        $cookie = GithubLogin::login($user->login, $user->email, $accessToken, $orgTeams);

        return Redirect::to('/')->withCookie($cookie);

    } else {
        return "ORG ERROR";
    }
});

Route::get('/', 'SystemController@index');

Route::post('/system/config/save', 'SystemController@systemConfig');

Route::post('/hostType/add', 'SystemController@addHostType');
Route::post('/hostType/del', 'SystemController@delHostType');

Route::post('/site/add', 'SystemController@addSite');
Route::post('/site/del', 'SystemController@delSite');

Route::post('/config/save', 'ConfigController@saveConfig');

Route::get('/test', function(){
    $user = GithubUser::create('sadfsdaf', 'asdfsdaf', 'asdfdsf');
    return $user->json();
});



Route::get('/site/config/{siteId}', 'ConfigController@config');

Route::get('/deploy/{siteId}', 'DeployController@index');

Route::get('/host/config/{siteId}', 'ConfigController@hostConfig');
Route::post('/host/add', 'ConfigController@hostAdd');
Route::post('/host/del', 'ConfigController@hostDel');






Route::post('/branch/deploy', 'DeployController@branch');
Route::post('/commit/deploy', 'DeployController@commit');
Route::post('/status/deploy', 'DeployController@status');