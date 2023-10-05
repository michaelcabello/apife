<?php

namespace App\Services;

use DateTime;
use Greenter\See; //importar
use Greenter\Model\Sale\Invoice;
use Greenter\Model\Client\Client;
use Greenter\Model\Company\Address;
use Greenter\Model\Company\Company;
use Illuminate\Support\Facades\Storage;
use Greenter\Ws\Services\SunatEndpoints;
use Greenter\Model\Sale\FormaPagos\FormaPagoContado;
use Greenter\Model\Sale\Legend;
use Greenter\Model\Sale\SaleDetail;


class SunatService
{
    public function getSee($company)
    {
        // configuraremos el certificado digital, la ruta del servicio y las credenciales (Clave SOL) a utilizar:
        $see = new See();
        $see->setCertificate(Storage::get($company->cert_path)); //le pasamos la ruta del certificado, da como resultado el contenido del certificado
        $see->setService($company->production ? SunatEndpoints::FE_PRODUCCION : SunatEndpoints::FE_BETA); //le indicamos si es beta o produccion
        $see->setClaveSOL($company->ruc, $company->sol_user, $company->sol_pass); //le pasamos los datos de la clave sol usurio secundario
        return $see; //retornamos todos los valores
    }

    public function getInvoice($data)
    {
        return (new Invoice())
            ->setUblVersion($data['ublVersion'])
            ->setTipoOperacion($data['tipoOperacion']) // Venta - Catalog. 51
            ->setTipoDoc($data['tipoDoc']) // Factura - Catalog. 01
            ->setSerie($data['serie'])
            ->setCorrelativo($data['correlativo'])
            ->setFechaEmision(new DateTime($data['fechaEmision'])) // Zona horaria: Lima
            ->setFormaPago(new FormaPagoContado()) // FormaPago: Contado
            ->setTipoMoneda($data['tipoMoneda']) // Sol - Catalog. 02
            ->setCompany($this->getCompany($data['company']))
            ->setClient($this->getClient($data['client']))
            ->setMtoOperGravadas($data['mtoOperGravadas'])
            ->setMtoIGV($data['mtoIGV'])
            ->setTotalImpuestos($data['totalImpuestos'])
            ->setValorVenta($data['valorVenta'])
            ->setSubTotal($data['subTotal'])
            ->setMtoImpVenta($data['mtoImpVenta'])
            ->setDetails($this->getDetails($data['details']))
            ->setLegends($this->getLegends($data['legends']));
    }

    public function getCompany($company)
    {
        return (new Company())
            ->setRuc($company['ruc'])
            ->setRazonSocial($company['razonSocial'])
            ->setNombreComercial($company['nombreComercial'])
            ->setAddress($this->getAddress($company['address']));
    }

    public function getClient($client)
    {
        return (new Client())
            ->setTipoDoc($client['tipoDoc'])
            ->setNumDoc($client['numDoc'])
            ->setRznSocial($client['rznSocial']);
    }



    public function getAddress($address)
    {
        return (new Address())
            ->setUbigueo($address['ubigeo'])
            ->setDepartamento($address['departamento'])
            ->setProvincia($address['provincia'])
            ->setDistrito($address['distrito'])
            ->setUrbanizacion($address['urbanizacion'])
            ->setDireccion($address['direccion'])
            ->setCodLocal($address['codLocal']);
    }


    public function getDetails($details)
    {
        $green_details = [];

        foreach ($details as $detail) {

            $green_details[] = (new SaleDetail())
                ->setTipAfeIgv($detail['tipAfeIgv']) // Gravado Op. Onerosa - Catalog. 07
                ->setCodProducto($detail['codProducto'])
                ->setUnidad($detail['unidad']) // Unidad - Catalog. 03
                ->setDescripcion($detail['descripcion'])
                ->setCantidad($detail['cantidad'])
                ->setMtoValorUnitario($detail['mtoValorUnitario'])
                ->setMtoValorVenta($detail['mtoValorVenta'])
                ->setMtoBaseIgv($detail['mtoBaseIgv'])
                ->setPorcentajeIgv($detail['porcentajeIgv']) // 18%
                ->setIgv($detail['igv'])

                ->setTotalImpuestos($detail['totalImpuestos']) // Suma de impuestos en el detalle

                ->setMtoPrecioUnitario($detail['mtoPrecioUnitario']);
        }


        return  $green_details;
    }

    public function getLegends($legends)
    {
        /* $green_legends = [];

       foreach($legends as $legend){
        $green_legends[] = (new Legend())
        ->setCode($legend['code'])
        ->setValue($legend['value']);
       }

        return $green_legends; */

       //la leyenda es uno solo y no es necesario hacer foreach
        $legend = (new Legend())
            ->setCode($legends['code']) // Monto en letras - Catalog. 52
            ->setValue($legends['value']);

        return [$legend];
    }

    public function sunatResponse($result)
    {

        $response['success'] = $result->isSuccess();

        // Verificamos que la conexión con SUNAT fue exitosa.
        if (!$response['success']) {

            $response['error'] = [
                'code' => $result->getError()->getCode(),
                'message' => $result->getError()->getMessage()
            ];

            return $response;
        }

        $response['cdrZip'] = base64_encode($result->getCdrZip());

        $cdr = $result->getCdrResponse();

        $response['cdrResponse'] = [
            'code' => (int)$cdr->getCode(),
            'description' => $cdr->getDescription(),
            'notes' => $cdr->getNotes()
        ];

        return $response;
    }
}
