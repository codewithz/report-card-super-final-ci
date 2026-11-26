<?php

use App\Models\Grade;
use App\Models\User;
use Database\Seeders\SchoolSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([SchoolSeeder::class, UserSeeder::class]);
});

// Liza Fernandez: adviser of Sampaguita (section 1), teaches Mathematics (teacher 1).
function lizaToken(): string
{
    return User::where('email', 'lfernandez@school.edu.ph')->first()
        ->createToken('test')->plainTextToken;
}

test('login with the right password gives a token', function () {
    $this->postJson('/api/login', [
        'email' => 'lfernandez@school.edu.ph',
        'password' => 'password',
        'device_name' => 'pest',
    ])->assertCreated()->assertJsonStructure(['token', 'name']);
});

test('login with a wrong password gives 401', function () {
    $this->postJson('/api/login', [
        'email' => 'lfernandez@school.edu.ph',
        'password' => 'guess',
        'device_name' => 'pest',
    ])->assertUnauthorized();
});

test('login without an email gives 422', function () {
    $this->postJson('/api/login', ['password' => 'password', 'device_name' => 'pest'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');
});

test('the API needs a token', function () {
    $this->getJson('/api/students/4/report-card')->assertUnauthorized();
});

test('a token gets the report card as JSON', function () {
    $this->withToken(lizaToken())->getJson('/api/students/4/report-card')
        ->assertOk()
        ->assertJsonPath('data.name', 'Bautista, Andrea')
        ->assertJsonPath('data.remark', 'INC');
});

test("a token cannot read another section's report card", function () {
    $this->withToken(lizaToken())->getJson('/api/students/6/report-card')->assertForbidden();
});

test('an unknown student gives 404', function () {
    $this->withToken(lizaToken())->getJson('/api/students/999/report-card')->assertNotFound();
});

test('a teacher sees only their own sections', function () {
    $this->withToken(lizaToken())->getJson('/api/sections')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Sampaguita');
});

test('a teacher can change a grade they gave', function () {
    $grade = Grade::where('teacher_id', 1)->first();

    $this->withToken(lizaToken())->putJson("/api/grades/{$grade->id}", ['grade' => 91.5])
        ->assertOk()
        ->assertJsonPath('data.grade', 91.5);
});

test("a teacher cannot change another teacher's grade", function () {
    $grade = Grade::where('teacher_id', 2)->first();

    $this->withToken(lizaToken())->putJson("/api/grades/{$grade->id}", ['grade' => 99])
        ->assertForbidden();
});

test('a grade above 100 gives 422', function () {
    $grade = Grade::where('teacher_id', 1)->first();

    $this->withToken(lizaToken())->putJson("/api/grades/{$grade->id}", ['grade' => 120])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('grade');
});

test('extra fields cannot move a grade to another teacher', function () {
    $grade = Grade::where('teacher_id', 1)->first();

    $this->withToken(lizaToken())->putJson("/api/grades/{$grade->id}", ['grade' => 90, 'teacher_id' => 2]);
    expect($grade->fresh()->teacher_id)->toBe(1);
});

test('logout cancels the token', function () {
    $token = lizaToken();

    $this->withToken($token)->postJson('/api/logout')->assertNoContent();
    $this->app['auth']->forgetGuards();
    $this->withToken($token)->getJson('/api/me')->assertUnauthorized();
});

test('the class list of my section has averages and remarks', function () {
    $this->withToken(lizaToken())->getJson('/api/sections/1/students')
        ->assertOk()
        ->assertJsonCount(6, 'data')
        ->assertJsonFragment(['name' => 'Bautista, Andrea', 'remark' => 'INC']);
});

test("the class list of another teacher's section gives 403", function () {
    $this->withToken(lizaToken())->getJson('/api/sections/2/students')->assertForbidden();
});