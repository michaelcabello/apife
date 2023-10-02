<?php

namespace App\Http\Controllers\Api;

use DateTime;
use Greenter\See;
use App\Models\Company;
use Illuminate\Http\Request;
use Greenter\Report\XmlUtils;
use App\Services\SunatService;
use Greenter\Model\Sale\Legend;
use Greenter\Model\Sale\Invoice;
use Greenter\Model\Client\Client;
use Greenter\Model\Company\Address;
use Greenter\Model\Sale\SaleDetail;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Greenter\Ws\Services\SunatEndpoints;
use Greenter\Model\Sale\FormaPagos\FormaPagoContado;
use Greenter\Model\Company\Company as CompanyCompany;


class InvoiceController extends Controller
{
    public function send(Request $request)
    {

        $company = Company::where('user_id', $request->user()->id)->firstOrFail();

        $sunat = new SunatService();//sunat service lo creamos nosotros
        $see = $sunat->getSee($company);//getSee esta en SunatService, getSee lo creamos nosotros
        $invoice = $sunat->getInvoice();
        $result = $see->send($invoice);//result necesita sunatResponse

        //return $sunat->sunatResponse($result);

        //$xml = $see->getFactory()->getLastXml();
        //return $xml;

        $response['xml'] = $see->getFactory()->getLastXml();
        $response['hash'] = (new XmlUtils())->getHashSign($response['xml']); //instalar el paquete greenter reports
        $response['sunatResponse'] = $sunat->sunatResponse($result);

        //return $response;
        return response()->json($response, 200);

    }
}
