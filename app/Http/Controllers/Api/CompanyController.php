<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Rules\UniqueRucRule;

class CompanyController extends Controller
{

    public function index()
    {
        //return "hola";
        //return JWTAuth::user();//para obtener el usuario autentificado
        //return "index";
        $companies = Company::where('user_id', auth()->id())->get();
        return response()->json($companies, 200);
    }


    public function store(Request $request)
    {
        //return "okok";

        $data = $request->validate([
            'razon_social' => 'required|string|max:255',
            'ruc' => [
                'required',
                'string',
                'regex:/^(10|20)\d{9}$/',
                new UniqueRucRule(JWTAuth::user()->id),
            ],
            'direccion' => 'required|string|max:255',
            'logo' => 'nullable|file|image',
            'sol_user' => 'required|string|max:255',
            'sol_pass' => 'required|string|max:255',

            'cert' => 'required|file|mimes:pem,txt',
            'client_id' => 'nullable|string|max:255',
            'client_secret' => 'nullable|string|max:255',
            'production' => 'nullable|boolean',
        ]);

        // return $request->all();

        //$cert = $request->file('cert');
        //return $cert->extension();//devuelve txt

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('logos');
        }

        $data['cert_path'] = $request->file('cert')->store('certs'); //SE recupera el archivo con cert y se sube a la carpeta certs
        $data['user_id'] = JWTAuth::user()->id;

        //return $data;

        $company = Company::create($data);

        return response()->json([
            'message' => 'Empresa creada correctamente',
            'company' => $company,
        ], 201);
    }



    public function show($company)
    {
        $company = Company::where('ruc', $company)
            ->where('user_id', auth()->user()->id)
            ->firstOrFail();
        return response()->json($company, 200);
    }


    public function update(Request $request, $company)
    {
        //return $company;

        $company = Company::where('ruc', $company)
        ->where('user_id', auth()->user()->id)
        ->firstOrFail();


        $data = $request->validate([
            'razon_social' => 'nullable|string|max:255',
            'ruc' => [
                'nullable',
                'string',
                'regex:/^(10|20)\d{9}$/',
                 new UniqueRucRule($company->id),
            ],
            'direccion' => 'nullable|string|max:255',
            'logo' => 'nullable|file|image',
            'sol_user' => 'nullable|string|max:255',
            'sol_pass' => 'nullable|string|max:255',

            'cert' => 'nullable|file|mimes:pem,txt',
            'client_id' => 'nullable|string|max:255',
            'client_secret' => 'nullable|string|max:255',
            'production' => 'nullable|boolean',
        ]);


        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('logos');
        }

        if ($request->hasFile('cert')) {
            $data['cert_path'] = $request->file('cert')->store('certs'); //SE recupera el archivo con cert y se sube a la carpeta certs
        }


        $company->update($data);

        return response()->json([
            'message' => 'Empresa actualizada',
            'company' => $company,
        ], 200);
    }


    public function destroy($company)
    {
        $company = Company::where('ruc', $company)
        ->where('user_id', auth()->user()->id)
        ->firstOrFail();

        $company->delete();

        return response()->json([
            'message'=>'Empresa eliminada correctamente',
        ],200);
    }
}
