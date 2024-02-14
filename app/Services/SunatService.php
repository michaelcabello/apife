<?php

namespace App\Services;

use DateTime;
use Greenter\See; //importar
use Greenter\Model\Sale\Note;
use Greenter\Model\Sale\Legend;
use Greenter\Report\HtmlReport;
use Greenter\Model\Sale\Invoice;
use Greenter\Model\Client\Client;
use Greenter\Model\Company\Address;
use Greenter\Model\Company\Company;
use Greenter\Model\Despatch\Driver;
use Greenter\Model\Sale\SaleDetail;
use Greenter\Model\Despatch\Vehicle;
use Greenter\Model\Despatch\Despatch;
use Greenter\Model\Despatch\Shipment;
use Greenter\Model\Despatch\Direction;
use Illuminate\Support\Facades\Storage;
use App\Models\Company as ModelsCompany;
use Greenter\Ws\Services\SunatEndpoints;
use Greenter\Model\Despatch\Transportist;
use Greenter\Model\Despatch\DespatchDetail;
use Greenter\Model\Sale\FormaPagos\FormaPagoContado;
use Greenter\Report\PdfReport;
use Greenter\Report\Resolver\DefaultTemplateResolver;

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
        //retorno invoice y le pasamos valores
        return (new Invoice())
            ->setUblVersion($data['ublVersion'] ?? '2.1')
            ->setTipoOperacion($data['tipoOperacion'] ?? null) // Venta - Catalog. 51
            ->setTipoDoc($data['tipoDoc'] ?? null) // Factura - Catalog. 01
            ->setSerie($data['serie'] ?? null)
            ->setCorrelativo($data['correlativo'] ?? null)
            ->setFechaEmision(new DateTime($data['fechaEmision']) ?? null) // Zona horaria: Lima
            ->setFormaPago(new FormaPagoContado()) // FormaPago: Contado
            ->setTipoMoneda($data['tipoMoneda']) // Sol - Catalog. 02
            ->setCompany($this->getCompany($data['company']))
            ->setClient($this->getClient($data['client']))

            //Mto Operaciones
            //aqui ya estan las operaciones calculadas
            ->setMtoOperGravadas($data['mtoOperGravadas'] ?? null)
            ->setMtoOperExoneradas($data['mtoOperExoneradas'] ?? null)
            ->setMtoOperInafectas($data['mtoOperInafectas'] ?? null)
            ->setMtoOperExportacion($data['mtoOperExportacion'] ?? null)
            ->setMtoOperGratuitas($data['mtoOperGratuitas'] ?? null)

            //Impuestos
            ->setMtoIGV($data['mtoIGV'])
            ->setMtoIGVGratuitas($data['mtoIGVGratuitas'])
            ->setIcbper($data['icbper'])
            ->setTotalImpuestos($data['totalImpuestos'])

            //Totales
            ->setValorVenta($data['valorVenta'])
            ->setSubTotal($data['subTotal'])
            ->setRedondeo($data['redondeo'])
            ->setMtoImpVenta($data['mtoImpVenta'])

            //Productos
            ->setDetails($this->getDetails($data['details']));

        //Leyendas
        //->setLegends($this->getLegends($data['legends']));


        /* ->setMtoIGV($data['mtoIGV'])
            ->setTotalImpuestos($data['totalImpuestos'])
            ->setValorVenta($data['valorVenta'])
            ->setSubTotal($data['subTotal'])
            ->setMtoImpVenta($data['mtoImpVenta'])
            ->setDetails($this->getDetails($data['details']))
            ->setLegends($this->getLegends($data['legends'])); */
    }


    public function getNote($data)
    {
        return (new Note)
            ->setUblVersion($data['ublVersion'] ?? '2.1')
            ->setTipoDoc($data['tipoDoc'] ?? null) // Factura - Catalog. 01
            ->setSerie($data['serie'] ?? null)
            ->setCorrelativo($data['correlativo'] ?? null)
            ->setFechaEmision(new DateTime($data['fechaEmision'] ?? null)) // Zona horaria: Lima
            ->setTipDocAfectado($data['tipDocAfectado'] ?? null) // si esta afectando a una factura o boleta los valores 01 y 03
            ->setNumDocfectado($data['numDocfectado'] ?? null)
            ->setCodMotivo($data['codMotivo'] ?? null)
            ->setDesMotivo($data['desMotivo'] ?? null)
            ->setTipoMoneda($data['tipoMoneda'] ?? null)
            ->setCompany($this->getCompany($data['company']))
            ->setClient($this->getClient($data['client']))

            //Mto Operaciones
            ->setMtoOperGravadas($data['mtoOperGravadas'] ?? null)
            ->setMtoOperExoneradas($data['mtoOperExoneradas'] ?? null)
            ->setMtoOperInafectas($data['mtoOperInafectas'] ?? null)
            ->setMtoOperExportacion($data['mtoOperExportacion'] ?? null)
            ->setMtoOperGratuitas($data['mtoOperGratuitas'] ?? null)

            //Impuestos
            ->setMtoIGV($data['mtoIGV'])
            ->setMtoIGVGratuitas($data['mtoIGVGratuitas'])
            ->setIcbper($data['icbper'])
            ->setTotalImpuestos($data['totalImpuestos'])

            //Totales
            ->setValorVenta($data['valorVenta'])
            ->setSubTotal($data['subTotal'])
            ->setRedondeo($data['redondeo'])
            ->setMtoImpVenta($data['mtoImpVenta'])

            //Productos
            ->setDetails($this->getDetails($data['details']))

            //Leyendas
            ->setLegends($this->getLegends($data['legends']));
    }


    //para envio de guias, se envia con un api, esta es la conexión
    public function getSeeApi($company)
    {
        $api = new \Greenter\Api($company->production ? [

            'auth' => 'https://api-seguridad.sunat.gob.pe/v1',
            'cpe' => 'https://api-cpe.sunat.gob.pe/v1',

        ] : [

            'auth' => 'https://gre-test.nubefact.com/v1',
            'cpe' => 'https://gre-test.nubefact.com/v1',

        ]);

        $api->setBuilderOptions([
            'strict_variables' => true,
            'optimizations' => 0,
            'debug' => true,
            'cache' => false,
        ])->setApiCredentials(
            $company->production ? $company->client_id : "test-85e5b0ae-255c-4891-a595-0b98c65c9854", //client_id
            $company->production ? $company->client_secret : "test-Hty/M6QshYvPgItX2P0+Kw==" //client_secreT
        )->setClaveSOL(
            $company->ruc,
            $company->production ? $company->sol_user : "MODDATOS",
            $company->production ? $company->sol_pass : "MODDATOS"
        )->setCertificate(Storage::get($company->cert_path));

        return $api;
    }

    //método para generar guias
    public function getDespatch($data)
    {
        return (new Despatch)
            ->setVersion($data['version'] ?? '2022')
            ->setTipoDoc($data['tipoDoc'] ?? '09') // Guia 09- ver Catalog. 01
            ->setSerie($data['serie'] ?? null)
            ->setCorrelativo($data['correlativo'] ?? null)
            ->setFechaEmision(new DateTime($data['fechaEmision'] ?? null)) // Zona horaria: Lima
            ->setCompany($this->getCompany($data['company']))
            ->setDestinatario($this->getClient($data['destinatario']))
            ->setEnvio($this->getEnvio($data['envio'])) //crearemos getEnvio

            //Productos crearemos el metodo getDespatchDetails
            ->setDetails($this->getDespatchDetails($data['details']));
    }


    public function getDespatchDetails($details)
    {
        $green_details = [];

        foreach ($details as $detail) {
            $green_details[] = (new DespatchDetail)
                ->setCantidad($detail['cantidad'] ?? null)
                ->setUnidad($detail['unidad'] ?? null) // Unidad - Catalog. 03
                ->setDescripcion($detail['descripcion'] ?? null)
                ->setCodigo($detail['codigo'] ?? null);
        }

        return $green_details;
    }

    public function getEnvio($data)
    {
        $shipment = (new Shipment)
            ->setCodTraslado($data['codTraslado'] ?? null) //saca del catalogo 20 de sunat ver https://cpe.sunat.gob.pe/sites/default/files/inline-files/anexoV-340-2017.pdf
            ->setModTraslado($data['modTraslado'] ?? null) //saca del catalogo 18
            ->setFecTraslado(new DateTime($data['fecTraslado'] ?? null))
            ->setPesoTotal($data['pesoTotal'] ?? null)
            ->setUndPesoTotal($data['undPesoTotal'] ?? null)
            ->setLlegada(new Direction($data['llegada']['ubigueo'], $data['llegada']['direccion']))
            ->setPartida(new Direction($data['partida']['ubigueo'], $data['partida']['direccion']));

        if ($data['modTraslado'] == '01') {//publico
            $shipment->setTransportista($this->getTransportista($data['transportista']));
        }

        if ($data['modTraslado'] == '02') {//privado
            $shipment->setVehiculo($this->getVehiculo($data['vehiculos']))
                ->setChoferes($this->getChoferes($data['choferes']));
        }

        return $shipment;
    }


    public function getTransportista($data)
    {
        return (new Transportist)
            ->setTipoDoc($data['tipoDoc'] ?? null) // DNI - Catalog. 06
            ->setNumDoc($data['numDoc'] ?? null)
            ->setRznSocial($data['rznSocial'] ?? null)
            ->setNroMtc($data['nroMtc'] ?? null);
    }


    public function getVehiculo($vehiculos)
    {

        $vehiculos = collect($vehiculos);

        $secundarios = [];

        foreach ($vehiculos->slice(1) as $item) {
            $secundarios[] = (new Vehicle())
                ->setPlaca($item['placa'] ?? null);
        }

        return (new Vehicle())
            ->setPlaca($vehiculos->first()['placa'] ?? null)
            ->setSecundarios($secundarios);

        /* [
            [
                'placa' => 'A1B-123',
            ],
            [
                'placa' => 'A1B-123',
            ],
            [
                'placa' => 'A1B-123',
            ]
        ] */

        /* $vehiculo = (new Vehicle())
                    ->setPlaca($data['placa'] ?? null);

        $vehiculoSecundario = (new Vehicle())
                    ->setPlaca($data['placaSecundaria'] ?? null);

        $vehiculo->getSecundarios([$vehiculoSecundario]); */
    }



    public function getChoferes($choferes)
    {
        $choferes = collect($choferes);

        $drivers = [];

        $drivers[] = (new Driver())
            ->setTipo('Principal')
            ->setTipoDoc($choferes->first()['tipoDoc'] ?? null) //https://www.sunat.gob.pe/legislacion/superin/2014/anexo8-300-2014.pdf    en el catalogo 6
            ->setNroDoc($choferes->first()['nroDoc'] ?? null)
            ->setLicencia($choferes->first()['licencia'] ?? null)
            ->setNombres($choferes->first()['nombres'] ?? null)
            ->setApellidos($choferes->first()['apellidos'] ?? null);

        foreach ($choferes->slice(1) as $item) //->slice(1) toma todos los valores excepto el primero(1) si fuera slice(2) no toma el 2
        {
            $drivers[] = (new Driver)
                ->setTipo('Secundario')
                ->setTipoDoc($item['tipoDoc'] ?? null)
                ->setNroDoc($item['nroDoc'] ?? null)
                ->setLicencia($item['licencia'] ?? null)
                ->setNombres($item['nombres'] ?? null)
                ->setApellidos($item['apellidos'] ?? null);
        }

        return $drivers;
    }




    public function getCompany($company)
    {
        return (new Company())
            ->setRuc($company['ruc'] ?? null)
            ->setRazonSocial($company['razonSocial'] ?? null)
            ->setNombreComercial($company['nombreComercial'] ?? null)
            ->setAddress($this->getAddress($company['address']) ?? null);
    }

    public function getClient($client)
    {
        return (new Client())
            ->setTipoDoc($client['tipoDoc'] ?? null)
            ->setNumDoc($client['numDoc'] ?? null)
            ->setRznSocial($client['rznSocial'] ?? null);
    }



    public function getAddress($address)
    {
        return (new Address())
            ->setUbigueo($address['ubigueo'] ?? null)
            ->setDepartamento($address['departamento'] ?? null)
            ->setProvincia($address['provincia'] ?? null)
            ->setDistrito($address['distrito'] ?? null)
            ->setUrbanizacion($address['urbanizacion'] ?? null)
            ->setDireccion($address['direccion'] ?? null)
            ->setCodLocal($address['codLocal'] ?? null);
    }


    public function getDetails($details)
    {
        $green_details = [];

        if ($details) {
            foreach ($details as $detail) {

                $green_details[] = (new SaleDetail())
                    ->setTipAfeIgv($detail['tipAfeIgv'] ?? null) // Gravado Op. Onerosa - Catalog. 07
                    ->setCodProducto($detail['codProducto'] ?? null)
                    ->setUnidad($detail['unidad'] ?? null) // Unidad - Catalog. 03
                    ->setDescripcion($detail['descripcion'] ?? null)
                    ->setCantidad($detail['cantidad'] ?? null)
                    ->setMtoValorUnitario($detail['mtoValorUnitario'] ?? null)
                    ->setMtoValorVenta($detail['mtoValorVenta'] ?? null)
                    ->setMtoBaseIgv($detail['mtoBaseIgv'] ?? null)
                    ->setPorcentajeIgv($detail['porcentajeIgv'] ?? null) // 18%
                    ->setIgv($detail['igv'] ?? null)
                    ->setFactorIcbper($detail['factorIcbper'] ?? null) //si esiste poner sino null
                    ->setIcbper($detail['icbper'] ?? null)

                    ->setTotalImpuestos($detail['totalImpuestos'] ?? null) // Suma de impuestos en el detalle

                    ->setMtoPrecioUnitario($detail['mtoPrecioUnitario'] ?? null);
            }
        }




        return  $green_details;
    }

    //public function getLegends($legends)
    //{
    /* $green_legends = [];

       foreach($legends as $legend){
        $green_legends[] = (new Legend())
        ->setCode($legend['code'])
        ->setValue($legend['value']);
       }

        return $green_legends; */

    //la leyenda es uno solo y no es necesario hacer foreach
    //$legend = (new Legend())
    //   ->setCode($legends['code']) // Monto en letras - Catalog. 52
    //   ->setValue($legends['value']);

    //return [$legend];
    //}

    public function sunatResponse($result) //result esta en InvoiceController
    {

        $response['success'] = $result->isSuccess(); //para que retorne el resultado en formato json

        // Verificamos que la conexión con SUNAT fue exitosa.
        //si la conexion no fue satifactoria
        if (!$response['success']) {

            $response['error'] = [
                'code' => $result->getError()->getCode(),
                'message' => $result->getError()->getMessage()
            ];

            return $response;
        }

        //base64_encode lo hace legible al cdr
        $response['cdrZip'] = base64_encode($result->getCdrZip());

        $cdr = $result->getCdrResponse();

        $response['cdrResponse'] = [
            'code' => (int)$cdr->getCode(),
            'description' => $cdr->getDescription(),
            'notes' => $cdr->getNotes()
        ];

        return $response;
    }

    //generando el reporte
    public function getHtmlReport($invoice)
    {

        //  dd("hola");

        $report = new HtmlReport();

        $resolver = new DefaultTemplateResolver();//vera si es factura boleta, ...

        $report->setTemplate($resolver->getTemplate($invoice));

        $ruc = $invoice->getCompany()->getRuc();
        $company = ModelsCompany::where('ruc', $ruc)->first();

        $params = [
            'system' => [
                'logo' => Storage::get($company->logo_path), // Logo de Empresa
                'hash' => 'qqnr2dN4p/HmaEA/CJuVGo7dv5g=', // Valor Resumen
            ],
            'user' => [
                'header'     => 'Telf: <b>(01) 123375</b>', // Texto que se ubica debajo de la dirección de empresa
                'extras'     => [
                    // Leyendas adicionales
                    ['name' => 'CONDICION DE PAGO', 'value' => 'Efectivo'],
                    ['name' => 'VENDEDOR', 'value' => 'GITHUB SELLER'],
                ],
                'footer' => '<p>Nro Resolucion: <b>3232323</b></p>'
            ]
        ];

        return $report->render($invoice, $params);
    }

    public function getLegends($legends)
    {
        $green_legends = [];

        foreach ($legends as $legend) {
            $green_legends[] = (new Legend())
                ->setCode($legend['code'] ?? null) // Monto en letras - Catalog. 52
                ->setValue($legend['value'] ?? null);
        }

        return $green_legends;
    }


    public function generatePdfReport($invoice)
    {
        $htmlReport = new HtmlReport();

        $resolver = new DefaultTemplateResolver();
        $htmlReport->setTemplate($resolver->getTemplate($invoice));

        $report = new PdfReport($htmlReport);
        // Options: Ver mas en https://wkhtmltopdf.org/usage/wkhtmltopdf.txt
        $report->setOptions( [
            'no-outline',
            'viewport-size' => '1280x1024',
            'page-width' => '21cm',
            'page-height' => '29.7cm',
        ]);

        $report->setBinPath(env('WKHTMLTOPDF_PATH'));

        $ruc = $invoice->getCompany()->getRuc();
        $company = ModelsCompany::where('ruc', $ruc)->first();

        $params = [
            'system' => [
                'logo' => Storage::get($company->logo_path), // Logo de Empresa
                'hash' => 'qqnr2dN4p/HmaEA/CJuVGo7dv5g=', // Valor Resumen
            ],
            'user' => [
                'header'     => 'Telf: <b>(01) 123375</b>', // Texto que se ubica debajo de la dirección de empresa
                'extras'     => [
                    // Leyendas adicionales
                    ['name' => 'CONDICION DE PAGO', 'value' => 'Efectivo'     ],
                    ['name' => 'VENDEDOR'         , 'value' => 'GITHUB SELLER'],
                ],
                'footer' => '<p>Nro Resolucion: <b>3232323</b></p>'
            ]
        ];

        $pdf = $report->render($invoice, $params);

        Storage::put('invoices/' . $invoice->getName() . '.pdf', $pdf);
    }




}
