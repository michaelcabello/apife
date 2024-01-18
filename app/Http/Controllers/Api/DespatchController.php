<?php

namespace App\Http\Controllers\Api;

use App\Models\Company;
use Illuminate\Http\Request;
use Greenter\Report\XmlUtils;
use App\Services\SunatService;
use App\Http\Controllers\Controller;

class DespatchController extends Controller
{
    public function send(Request $request)
    {
        //return "enviando";

        $data = $request->all();

        //return $data;

        $company = Company::where('user_id', auth()->id())
            ->where('ruc', $data['company']['ruc'])
            ->firstOrFail();

        $sunat = new SunatService();
        $despatch = $sunat->getDespatch($data);

        $api = $sunat->getSeeApi($company);
        $result = $api->send($despatch);

        $ticket = $result->getTicket();
        $result = $api->getStatus($ticket);


        $response['xml'] = $api->getLastXml();
        $response['hash'] = (new XmlUtils())->getHashSign($response['xml']);
        $response['sunatResponse'] = $sunat->sunatResponse($result);

        return response()->json($response, 200);

        //return $ticket;
    }

    public function xml(Request $request)
    {
        $data = $request->all();

        $company = Company::where('user_id', auth()->id())
            ->where('ruc', $data['company']['ruc'])
            ->firstOrFail();

        $sunat = new SunatService;
        $see = $sunat->getSee($company);
        $despatch = $sunat->getDespatch($data);

        $response['xml'] = $see->getXmlSigned($despatch);
        $response['hash'] = (new XmlUtils())->getHashSign($response['xml']);

        return $response;


    }

    public function pdf(Request $request)
    {
        $data = $request->all();

        $company = Company::where('user_id', auth()->id())
                    ->where('ruc', $data['company']['ruc'])
                    ->firstOrFail();

        $sunat = new SunatService;
        $despatch = $sunat->getDespatch($data);

        return $sunat->getHtmlReport($despatch);

    }
}
