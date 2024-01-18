<?php

namespace App\Http\Controllers\Api;

use App\Models\Company;
use App\Traits\SunatTrait;
use Illuminate\Http\Request;
use Greenter\Report\XmlUtils;
use App\Services\SunatService;
use App\Http\Controllers\Controller;
use Greenter\Model\Company\Company as CompanyCompany;

class NoteController extends Controller
{
    use SunatTrait;

    public function send(Request $request)
    {
        //return "notaaaaaaaa";

        $request->validate([
            'company' => 'required|array',
            'company.address' => 'required|array',
            'client' => 'required|array',
            'details' => 'required|array',
            'details.*' => 'required|array',
        ]);

        $data = $request->all();


        $company = Company::where('user_id', auth()->id())
                    ->where('ruc', $data['company']['ruc'])
                    ->firstOrFail();

        /*  return $company; */

        $this->setTotales($data);
        $this->setLegends($data);

        //return $data;

        $sunat = new SunatService();
        $see = $sunat->getSee($company);
        $note = $sunat->getNote($data);

        $result = $see->send($note); //envia la factura a sunat

        $response['xml'] = $see->getFactory()->getLastXml();
        $response['hash'] = (new XmlUtils())->getHashSign($response['xml']);
        $response['sunatResponse'] = $sunat->sunatResponse($result);

        return response()->json($response, 200);
    }

    public function xml(Request $request)
    {
        $request->validate([
            'company' => 'required|array',
            'company.address' => 'required|array',
            'client' => 'required|array',
            'details' => 'required|array',
            'details.*' => 'required|array',
        ]);

        $data = $request->all();


        $company = Company::where('user_id', auth()->id())
                    ->where('ruc', $data['company']['ruc'])
                    ->firstOrFail();

        /*  return $company; */

        $this->setTotales($data);
        $this->setLegends($data);

        $sunat = new SunatService();
        $see = $sunat->getSee($company);
        $note = $sunat->getNote($data);

        $response['xml'] = $see->getXmlSigned($note);
        $response['hash'] = (new XmlUtils())->getHashSign($response['xml']);

        return response()->json($response, 200);

    }

    public function pdf(Request $request)
    {
        $request->validate([
            'company' => 'required|array',
            'company.address' => 'required|array',
            'client' => 'required|array',
            'details' => 'required|array',
            'details.*' => 'required|array',
        ]);

        $data = $request->all();

        $company = Company::where('user_id', auth()->id())
                    ->where('ruc', $data['company']['ruc'])
                    ->firstOrFail();

        $this->setTotales($data);
        $this->setLegends($data);

        $sunat = new SunatService;
        $note = $sunat->getNote($data);

        //$pdf = $sunat->getHtmlReport($invoice);

        //$sunat->generatePdfReport($note);

        return $sunat->getHtmlReport($note);
    }

}
