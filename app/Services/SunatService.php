<?php

namespace App\Services;

use DateTime;
use Greenter\See;//importar
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
        $see->setCertificate(Storage::get($company->cert_path));//le pasamos la ruta del certificado, da como resultado el contenido del certificado
        $see->setService($company->production ? SunatEndpoints::FE_PRODUCCION : SunatEndpoints::FE_BETA);//le indicamos si es beta o produccion
        $see->setClaveSOL($company->ruc, $company->sol_user, $company->sol_pass);//le pasamos los datos de la clave sol usurio secundario
        return $see; //retornamos todos los valores
    }

    public function getInvoice()
    {
        return (new Invoice())
            ->setUblVersion('2.1')
            ->setTipoOperacion('0101') // Venta - Catalog. 51
            ->setTipoDoc('01') // Factura - Catalog. 01
            ->setSerie('F001')
            ->setCorrelativo('1')
            ->setFechaEmision(new DateTime()) // Zona horaria: Lima
            ->setFormaPago(new FormaPagoContado()) // FormaPago: Contado
            ->setTipoMoneda('PEN') // Sol - Catalog. 02
            ->setCompany($this->getCompany())
            ->setClient($this->getClient())
            ->setMtoOperGravadas(100.00)
            ->setMtoIGV(18.00)
            ->setTotalImpuestos(18.00)
            ->setValorVenta(100.00)
            ->setSubTotal(118.00)
            ->setMtoImpVenta(118.00)
            ->setDetails($this->getDetails())
            ->setLegends($this->getLegends());
    }

    public function getCompany()
    {
        return (new Company())
            ->setRuc('20123456789')
            ->setRazonSocial('GREEN SAC')
            ->setNombreComercial('GREEN')
            ->setAddress($this->getAddress());
    }

    public function getClient()
    {
        return (new Client())
            ->setTipoDoc('6')
            ->setNumDoc('20000000001')
            ->setRznSocial('EMPRESA X');
    }



    public function getAddress()
    {
        return (new Address())
            ->setUbigueo('150101')
            ->setDepartamento('LIMA')
            ->setProvincia('LIMA')
            ->setDistrito('LIMA')
            ->setUrbanizacion('-')
            ->setDireccion('Av. Villa Nueva 221')
            ->setCodLocal('0000');
    }


    public function getDetails()
    {
        $item = (new SaleDetail())
            ->setCodProducto('P001')
            ->setUnidad('NIU') // Unidad - Catalog. 03
            ->setCantidad(2)
            ->setMtoValorUnitario(50.00)
            ->setDescripcion('PRODUCTO 1')
            ->setMtoBaseIgv(100)
            ->setPorcentajeIgv(18.00) // 18%
            ->setIgv(18.00)
            ->setTipAfeIgv('10') // Gravado Op. Onerosa - Catalog. 07
            ->setTotalImpuestos(18.00) // Suma de impuestos en el detalle
            ->setMtoValorVenta(100.00)
            ->setMtoPrecioUnitario(59.00);

        return [$item];
    }

    public function getLegends()
    {
        $legend = (new Legend())
            ->setCode('1000') // Monto en letras - Catalog. 52
            ->setValue('SON DOSCIENTOS TREINTA Y SEIS CON 00/100 SOLES');

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
