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
        $data = $request->all();
        //return $data;

       // $company = Company::where('user_id', $request->user()->id)->firstOrFail();
       $company = Company::where('user_id', auth()->id())
       ->where('ruc', $data['company']['ruc'])
       ->firstOrFail();

       //return $company;

        $sunat = new SunatService();//sunat service lo creamos nosotros
        $see = $sunat->getSee($company);//getSee esta en SunatService, getSee lo creamos nosotros
        $invoice = $sunat->getInvoice($data);//aqui esta los datos de la boleta o factura
        $result = $see->send($invoice);//aqui se envia el comprobante, result necesita sunatResponse

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
