<?php

namespace App\Http\Controllers;

use App\Helpers\JWTAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\User;
use Illuminate\Http\Response;

class UserController extends Controller
{
    public function pruebas(Request $request)
    {
        return "Acción de pruebas de UserController";
    }

    public function register(Request $request)
    {
        // Recoger los datos del usuario por POST

        $json = $request->input('json', null);

        $params = json_decode($json); //Objeto
        $params_array = json_decode($json, true); //Array

        if (!empty($params) && !empty($params_array)) {
            // Limpiar datos

            $params_array = array_map('trim', $params_array);


            // Validar datos

            $validate = \Validator::make($params_array, [
                'name'      => 'required|alpha',
                'surname'   => 'required|alpha',
                'email'     => 'required|email|unique:users', // Comprobar si el usuario existe(duplicado)
                'password'  => 'required'
            ]);

            if ($validate->fails()) {
                $data = array(
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'El usuario no se ha creado',
                    'errors' => $validate->errors()
                );
            } else {    // Validacion correcta
                // Cifrar la contraseña
                $pwd = hash('sha256', $params->password);

                // Crear objeto de usuario
                $user = new User();
                $user->name = $params_array['name'];
                $user->surname = $params_array['surname'];
                $user->email = $params_array['email'];
                $user->password = $pwd;
                $user->role = "ROLE_USER";

                // Guardar usuario

                $user->save();



                $data = array(
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'El usuario se ha creado correctamente',
                    'user' => $user
                );
            }
        } else {
            $data = array(
                'status' => 'error',
                'code' => 404,
                'message' => 'Los datos enviados no son correctos'
            );
        }


        return response()->json($data, $data['code']);
    }

    public function login(Request $request)
    {
        $jwtAuth = new \JwtAuth();

        // RECIBIR DATOS POR POST

        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);
        // VALIDAR ESOS DATOS

        $validate = \Validator::make($params_array, [
            'email'     => 'required|email',
            'password'  => 'required'
        ]);

        if ($validate->fails()) {
            $signup = array(
                'status' => 'error',
                'code' => 404,
                'message' => 'El usuario no se ha podido identificar',
                'errors' => $validate->errors()
            );
        } else {
            // CIFRAR CONTRASEÑA
            $pwd = hash('sha256', $params->password);
            // DEVOLVER TOKEN O DATOS
            $signup = $jwtAuth->signup($params->email, $pwd);

            if (!empty($params->getToken)) {
                $signup = $jwtAuth->signup($params->email, $pwd, true);
            }
        }

        return response()->json($signup, 200);
    }


    public function update(Request $request)
    {
        // Comprobar si el usuario está identificado
        $token = $request->header('Authorization');
        $jwtAuth = new \JwtAuth();
        $checkToken = $jwtAuth->checkToken($token);

        // Recoger datos por POST
        $json = $request->input('json', null);
        $params_array = json_decode($json, true);

        if ($checkToken && !empty($params_array)) {


            // Obtener usuario identificado

            $user = $jwtAuth->checkToken($token, true);

            // Validar datos

            $validate = \Validator::make($params_array, [
                'name'      => 'required|alpha',
                'surname'   => 'required|alpha',
                'email'     => 'required|email|unique:users,' . $user->sub // Comprobar si el usuario existe(duplicado)
            ]);
            // Quitar campos que no quiero actualizar

            unset($params_array['id']);
            unset($params_array['role']);
            unset($params_array['password']);
            unset($params_array['created_at']);
            unset($params_array['remember_token']);
            // Actualizar usuario en la base de datos
            $user_update = User::where('id', $user->sub)->update($params_array);
            // Devolver array con el resultado

            $data = array(
                'code' => 200,
                'status' => 'success',
                'user' => $user,
                'changes' => $params_array
            );
        } else {
            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'El usuario no está identificado.'
            );
        }

        return response()->json($data, $data['code']);
    }

    public function upload(Request $request)
    {
        // Recoger los datos de la peticion

        $image = $request->file('file0');

        // Validar imagen

        $validate = \Validator::make($request->all(), [
            'file0' => 'required|image|mimes:jpg,jpeg,png,gif'
        ]);

        // Guardar la imagen en un disco

        if (!$image || $validate->fails()) {
            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'Error al subir la imagen'
            );
        } else {
            $image_name = time() . $image->getClientOriginalName();
            \Storage::disk('users')->put($image_name, \File::get($image));

            $data = array(
                'code' => 200,
                'status' => 'success',
                'image' => $image_name
            );
        }

        return response()->json($data, $data['code']);
    }

    public function getImage($filename){
        $isset = \Storage::disk('users')->exists($filename);
        if($isset){
            $file = \Storage::disk('users')->get($filename);
            return new Response($file, 200);
        } else {
            $data = array(
                'code' => 404,
                'status' => 'error',
                'message' => 'La imagen no existe'
            );

            return response()->json($data, $data['code']);
        }

    }

    public function details($id){
        $user = User::find($id);

        if(is_object($user)){
            $data = array(
                'code' => 200,
                'status' => 'success',
                'user' => $user
            );
        } else {
            $data = array(
                'code' => 404,
                'status' => 'error',
                'message' => 'El usuario no existe'
            );
        }
        return response()->json($data, $data['code']);
    }
}
