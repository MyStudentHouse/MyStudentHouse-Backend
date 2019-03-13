<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HouseControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Creates a house
     *
     * @return void
     */
    public function testCreateHouse()
    {
        /* Create a house */
        $data = [
            'name' => "AsproBruisHuis",
            'description' => "This is Aspro",
        ];

        $user = factory(\App\User::class)->create();
        $response = $this->actingAs($user, 'api')->json('POST', '/api/house', $data);
        $response->assertStatus(200);
        $response->assertJson([
            "success" => [
                "name" => "AsproBruisHuis",
                "description" => "This is Aspro",
                "created_by" => $user->id,
                /* TODO(PATBRO): resolve issue that sometimes return value of now() does not match with creation timestamp */
                "created_at" => now()->toDateTimeString(),
                "updated_at" => now()->toDateTimeString()
            ]
        ]);
    }

    public function testFetchHouse()
    {
        /* Create a house */
        $data = [
            'name' => "AsproBruisHuis 2.0",
            'description' => "This is Aspro 2.0",
        ];

        $user = factory(\App\User::class)->create();
        $response = $this->actingAs($user, 'api')->json('POST', '/api/house', $data);
        $response->assertStatus(200);

        /* Fetch the created house */
        $data = [
            'house_id' => 1,
        ];

        $response = $this->actingAs($user, 'api')->json('POST', '/api/house/fetch', $data);
        $response->assertStatus(200);
        $response->assertJson([
            "success" => [[
                "id" => 1,
                "name" => "AsproBruisHuis 2.0",
                "description" => "This is Aspro 2.0",
                "image" => "/img/placeholders/house_placeholder.jpg",
                "created_by" => $user->id,
                "created_at" => now()->toDateTimeString(),
                "updated_at" => now()->toDateTimeString()
            ]]
        ]);
    }

    public function testAssignUserToHouse()
    {
        /* Assign user to a house */
        $user = factory(\App\User::class)->create();

        $data = [
            'house_id' => 1,
            'user_id' => $user->id,
            'role' => 1,
        ];

        $response = $this->actingAs($user, 'api')->json('POST', '/api/house/assign', $data);
        $response->assertStatus(200);
        $response->assertJson([
            "success" => [
                "house_id" => 1,
                "user_id" => $user->id,
                "role" => 1,
                "created_at" => now()->toDateTimeString(),
                "updated_at" => now()->toDateTimeString()
            ]
        ]);

        $data = [
            'house_id' => 1,
            'user_id' => 1,
        ];

        $response = $this->actingAs($user, 'api')->json('POST', '/api/house/validate', $data);
        $response->assertStatus(200);
        $response->assertJson([
            "success" => true
        ]);
    }
}
