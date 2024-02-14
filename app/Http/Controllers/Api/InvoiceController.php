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
use App\Traits\SunatTrait;
use Illuminate\Support\Facades\Storage;
use Greenter\Ws\Services\SunatEndpoints;
use Luecano\NumeroALetras\NumeroALetras;
use Greenter\Model\Sale\FormaPagos\FormaPagoContado;
use Greenter\Model\Company\Company as CompanyCompany;


class InvoiceController extends Controller
{
    use SunatTrait;


    public function send(Request $request)
    {
        //validando informacion enviada
        $request->validate([
            'company' => 'required|array',
            'company.address' => 'required|array',
            'client' => 'required|array',
            'details' => 'required|array',
            'details.*' => 'required|array',
        ]);

        $data = $request->all();
        //return $data;
       // $company = Company::where('user_id', $request->user()->id)->firstOrFail();
       //obtenemos la compañia asignada al usuario lgueado y con ruc enviado
       $company = Company::where('user_id', auth()->id())
       ->where('ruc', $data['company']['ruc'])
       ->firstOrFail();

        $this->setTotales($data);
        $this->setLegends($data);

       //return $data;
       //return $company;
        $sunat = new SunatService();//sunat service lo creamos nosotros
        $see = $sunat->getSee($company);//getSee esta en SunatService, getSee lo creamos nosotros
        $invoice = $sunat->getInvoice($data);//aqui esta los datos de la boleta o factura
        $result = $see->send($invoice);//aqui se envia el comprobante, result necesita sunatResponse

        //return $sunat->sunatResponse($result);
        //
        //$xml = $see->getFactory()->getLastXml();
        //return $xml;
        //$see->getFactory()->getLastXml()  retorna el xml
        $response['xml'] = $see->getFactory()->getLastXml();//podras tener acceso al xml
        $response['hash'] = (new XmlUtils())->getHashSign($response['xml']); //instalar el paquete greenter reports  composer require greenter/report
        $response['sunatResponse'] = $sunat->sunatResponse($result);
        //return $response;
        return response()->json($response, 200);
    }




    //generando el xml
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

        $this->setTotales($data);
        $this->setLegends($data);

        $sunat = new SunatService;
        $see = $sunat->getSee($company);
        $invoice = $sunat->getInvoice($data);

        $response['xml'] = $see->getXmlSigned($invoice);
        $response['hash'] = (new XmlUtils())->getHashSign($response['xml']);

        return $response;
    }


    /* public function pdf(Request $request){
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
        $see = $sunat->getSee($company);
        $invoice = $sunat->getInvoice($data);

        return $sunat->getHtmlReport($invoice);
    } */


    public function pdf(Request $request){
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
        //$see = $sunat->getSee($company);
        $invoice = $sunat->getInvoice($data);

       //$pdf = $sunat->generatePdfReport($invoice);

        return $sunat->getHtmlReport($invoice);
    }

}
