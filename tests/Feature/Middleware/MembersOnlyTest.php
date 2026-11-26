<?php

use App\Models\User;

beforeEach(function () {
    installHotel();
    config(['habbo.members_only' => true]);
});

test('guests get only the login form', function () {
    $this->get(route('welcome'))
        ->assertOk()
        ->assertSee('action="' . route('login') . '"', false)
        ->assertDontSee(route('register'), false)
        ->assertDontSee(route('article.index'), false);
});

test('guests are sent to the login page from every other page', function (string $path) {
    $this->get($path)->assertRedirect(route('login'));
})->with([
    'me page' => '/user/me',
    'articles' => '/community/articles',
    'rules' => '/help-center/rules',
    'leaderboard' => '/leaderboard',
    'client' => '/game/nitro',
    'registration' => '/register',
]);

test('guests can not tell which usernames exist', function () {
    $user = User::factory()->create();

    $this->get(route('home.show', $user->username))->assertRedirect(route('login'));
    $this->get('/home/NoSuchUser')->assertRedirect(route('login'));
});

test('guests can not use the api', function (string $path) {
    $this->getJson($path)->assertUnauthorized();
})->with([
    'user lookup' => '/api/user/NoSuchUser',
    'user search' => '/api/users/search?q=a',
    'online users' => '/api/online-users',
    'v1 users' => '/api/v1/users',
    'v1 articles' => '/api/v1/articles',
]);

test('guests can not register', function () {
    $this->post(route('register.store'), [
        'username' => 'Stranger',
        'mail' => 'stranger@example.com',
        'password' => 'Password123',
        'password_confirmation' => 'Password123',
        'terms' => 'on',
    ])->assertRedirect(route('login'));

    expect(User::where('username', 'Stranger')->exists())->toBeFalse();
});

test('guests can log in and land on the page they asked for', function () {
    $user = User::factory()->create();

    $this->get('/community/articles')->assertRedirect(route('login'));

    $this->post(route('login.store'), ['username' => $user->username, 'password' => 'password'])
        ->assertRedirect(route('article.index'));

    $this->assertAuthenticatedAs($user);
});

test('guests can reach the housekeeping login', function () {
    $this->get('/housekeeping/login')->assertOk();

    expect($this->post(route('default-livewire.update'))->isRedirect(route('login')))->toBeFalse();
});

test('members can use the whole hotel', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('article.index'))
        ->assertOk()
        ->assertSee(route('leaderboard.index'), false);
});

test('the hotel is public when members only is off', function () {
    config(['habbo.members_only' => false]);

    $this->get(route('welcome'))->assertOk()->assertSee(route('register'), false);
    $this->get(route('article.index'))->assertOk();
});
