<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\User;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PostControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_store()
    {
        $user = User::factory()->create();

        // Llamada al manejador de errores para mostrar de manera más concisa la falla.
        // $this->withExceptionHandling();
        // $this->withoutExceptionHandling();
        /* 
            Simulación de una aplicación esta llegando mediante la ruta
            /api/posts y esta intentando guardar los datos mediante el
            método post.
        */
        $response = $this->actingAs($user, 'api')->json('POST', '/api/posts', [
            'title' => 'El post de prueba'
        ]);

        /* 
            Comprobar que se esta guardando los datos
            ->assertJsonStructure() : confirmación de estructura
            ->assertJson() : Confirmación de dato existente en Json
            ->assertStatus(201) : Confirmación de status

        */
        $response->assertJsonStructure(['id', 'title', 'created_at', 'updated_at'])
            ->assertJson(['title' => 'El post de prueba'])
            ->assertStatus(201);

        $this->assertDatabaseHas('posts', ['title' => 'El post de prueba']);
    }

    public function test_validate_title()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user, 'api')->json('POST', '/api/posts', [
            'title' => ''
        ]);

        //Solicitud bien hecha pero imposible completarla
        $response->assertStatus(422)
            ->assertJsonValidationErrors('title');
    }

    public function test_show()
    {
        $user = User::factory()->create();
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $response = $this->actingAs($user, 'api')->json('GET', "/api/posts/$post->id",); //id = 1

        $response->assertJsonStructure(['id', 'title', 'created_at', 'updated_at'])
            ->assertJson(['title' => $post->title])
            ->assertStatus(200);
    }

    public function test_404_show()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->json('GET', '/api/posts/1000',);

        $response->assertStatus(404);
    }

    public function test_update()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $response = $this->actingAs($user, 'api')->json('PUT', "/api/posts/$post->id", [
            'title' => 'nuevo'
        ]);


        $response->assertJsonStructure(['id', 'title', 'created_at', 'updated_at'])
            ->assertJson(['title' => 'nuevo'])
            ->assertStatus(200);

        $this->assertDatabaseHas('posts', ['title' => 'nuevo']);
    }

    public function test_delete()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $response = $this->actingAs($user, 'api')->json('DELETE', "/api/posts/$post->id");


        $response->assertSee(null)
            ->assertStatus(204); //Sin contenido ...

        $this->assertDatabaseMissing('posts', ['title' => $post->id]);
    }

    public function test_index()
    {
        $user = User::factory()->create();
        Post::factory()->count(5)->create();
        $response = $this->actingAs($user,'api')->json('GET', '/api/posts');

        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'title', 'created_at', 'updated_at']
            ]
        ])->assertStatus(200);
    }

    public function test_guest()
    {
        //Inidica que no está autorizado el acceso (HTTP 401)
        $this->json('GET', '/api/posts')->assertStatus(401);
        $this->json('POST', '/api/posts')->assertStatus(401);
        $this->json('GET', '/api/posts/1000')->assertStatus(401);
        $this->json('PUT', '/api/posts/1000')->assertStatus(401);
        $this->json('DELETE', '/api/posts/1000')->assertStatus(401);
    }
}
