<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Post;
use \App\Helpers\JWTAuth;

class PostController extends Controller
{
    public function __construct()
    {
        $this->middleware('api.auth', ['except' => ['index', 'show', 'getImage', 'getByCategory', 'getByUser']]);
    }

    public function index()
    {
        $posts = Post::all()->load('category');

        $data = array(
            'code' => 200,
            'status' => 'success',
            'posts' => $posts
        );

        return response()->json($data, $data['code']);
    }

    public function show($id)
    {
        $post = Post::find($id)->load('category');

        if (is_object($post)) {

            $data = array(
                'code' => 200,
                'status' => 'success',
                'post' => $post
            );
        } else {
            $data = array(
                'code' => 404,
                'status' => 'error',
                'message' => 'No se ha encontrado el post'
            );
        }

        return response()->json($data, $data['code']);
    }

    public function store(Request $request)
    {
        // Recoger datos por POST
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);

        if (!empty($params_array)) {
            // Conseguir datos del usuario identificado

            $user = $this->getIdentity($request);

            // Validar los datos

            $validate = \Validator::make($params_array, [
                'title' => 'required',
                'content' => 'required',
                'category_id' => 'required',
                'image' => 'required'
            ]);

            if ($validate->fails()) {
                $data = array(
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'Faltan datos para crear el post'
                );
            } else {
                // Guardar el post

                $post = new Post();
                $post->user_id = $user->sub;
                $post->category_id = $params->category_id;
                $post->title = $params->title;
                $post->content = $params->content;
                $post->image = $params->image;

                $post->save();

                $data = array(
                    'code' => 200,
                    'status' => 'success',
                    'post' => $post
                );
            }
        } else {
            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'No se ha podido crear el post'
            );
        }
        // Devolver la respuesta

        return response()->json($data, $data['code']);
    }

    public function update($id, Request $request)
    {
        $user = $this->getIdentity($request);
        // Conseguir el post
        $post = Post::find($id);
        if ($post->user_id == $user->sub) {

            // Recoger datos por POST

            $json = $request->input('json', null);

            $params = json_decode($json);
            $params_array = json_decode($json, true);


            if (!empty($params_array)) {

                // Validar los datos
                $validate = \Validator::make($params_array, [
                    'title' => 'required',
                    'content' => 'required',
                    'category_id' => 'required'
                ]);
                if ($validate->fails()) {
                    $data = array(
                        'code' => 400,
                        'status' => 'error',
                        'message' => 'Rellena todos los campos correctamente'
                    );
                } else {
                    // Eliminar los campos que no queremos actualizar
                    unset($params_array['id']);
                    unset($params_array['user_id']);
                    unset($params_array['created_at']);
                    unset($params_array['user']);
                    // Actualizar los datos en la base de datos
                    $post = Post::where('id', $id)->updateOrCreate($params_array);
                    $data = array(
                        'code' => 200,
                        'status' => 'success',
                        'changes' => $params_array,
                        'post' => $post
                    );
                }
            } else {
                $data = array(
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'Rellena todos los campos correctamente'
                );
            }
        } else {
            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'No eres el dueño del post'
            ); 
         }


        // Devolver una respuesta

        return response()->json($data, $data['code']);
    }

    public function destroy($id, Request $request)
    {
        $user = $this->getIdentity($request);
        
        // Conseguir el post
        $post = Post::find($id);
        if ($post->user_id == $user->sub) {
            if ($post) {
                // Borrar el registro
                $post->delete();
                $data = array(
                    'code' => 200,
                    'status' => 'success',
                    'post' => $post
                );
            } else {
                $data = array(
                    'code' => 404,
                    'status' => 'error',
                    'message' => 'No existe el post'
                );
            }
        } else {
            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'No eres el dueño del post'
            );
        }
        // Devolver respuesta

        return response()->json($data, $data['code']);
    }

    private function getIdentity(Request $request){
        $jwtAuth = new JwtAuth();
        $token = $request->header('Authorization');
        $user = $jwtAuth->checkToken($token, true);

        return $user;
    }

    public function upload(Request $request){
        // Recoger la imagen de la peticion
        $image = $request->file('file0');
        // Validar la imagen
        $validate = \Validator::make($request->all(), [
            'file0' => 'required|image|mimes:jpg,jpeg,png,gif'
        ]);
        // Guardar la imagen
        if(!$image || $validate->fails()){
            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'Error al subir la imagen'
            );
        } else {
            $image_name = time().$image->getClientOriginalName();
            \Storage::disk('images')->put($image_name, \File::get($image));

            $data = array(
                'code' => 200,
                'status' => 'success',
                'image' => $image_name
            );
        }
        // Devolver respuesta
        return response()->json($data, $data['code']);
    }

    public function getImage($filename){
        // Comprobar si existe la image
        $isset = \Storage::disk('images')->exists($filename);

        if($isset){
            // Devolver imagen
            $image = \Storage::disk('images')->get($filename);
            return new Response($image, 200);
        } else {
            $data = array(
                'code' => 404,
                'status' => 'error',
                'message' => 'No se ha encontrado la imagen'
            );
        }
        return response()->json($data, $data['code']);
    }

    public function getByUser($id){
        $posts = Post::where('user_id', $id)->get();
        return response()->json([
            'status' => 'success',
            'posts' => $posts
        ], 200);
    }

    public function getByCategory($id){
        $posts = Post::where('category_id', $id)->get();
        return response()->json([
            'status' => 'success',
            'posts' => $posts
        ], 200);

    }
}
