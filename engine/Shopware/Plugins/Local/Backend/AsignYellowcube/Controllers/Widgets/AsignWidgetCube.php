<?php

/**
 * This file defines the widget controllers
 *
 * PHP version 5
 *
 * @category  asign
 * @package   AsignYellowcube_v2.0_CE_5.1
 * @author    entwicklung@a-sign.ch
 * @copyright asign
 * @license   http://www.a-sign.ch/
 * @version   2.0
 * @link      http://www.a-sign.ch/
 * @see       Shopware_Controllers_Widgets_AsignWidgetCube
 * @since     File available since Release 1.0
 */

use Shopware\AsignYellowcube\Components\Api\AsignYellowcubeCore;

/**
* Defines winget controller
*
* @category Asign
* @package  AsignYellowcube_v2.0_CE_5.1
* @author   entwicklung@a-sign.ch
* @link     http://www.a-sign.ch
*/
class Shopware_Controllers_Widgets_AsignWidgetCube extends Enlight_Controller_Action
{
	 /**
     * Triggers action for generating pdf and sending order to Yellowcube
     * only when the order is completed.
     *
     * @var void
     */
    public function triggerYellowcubeAction()
	{
	    // get order-nr since its the only value present
        $oSession = Shopware()->Session();
	    $ordnr = $oSession['sOrderVariables']['sOrderNumber'];

	    try{
	        //1. get order information based on ID
            $oModel = Shopware()->Models()->getRepository("Shopware\CustomModels\AsignModels\Orders\Orders");
            $aOrders = $oModel->getOrderDetails($ordnr, true);
            
            // update the orderarticles with Tara, Tariff and Origin info
            $oModel->updateHandlingInfo($aOrders['orderarticles']);

	    // is the manual order sending enabled?
	    $isManual = $this->getPluginConfig()->blYellowCubeOrderManualSend;
			
            // if its not Prepayment(id=5) then proceed?
            if ($aOrders['paymentid'] != "5" && !$isManual) {
                //2. create the Document and save it...
                $orderId = $aOrders['ordid'];
                $documentType = 5;// modified version of invoice tpl
                $this->createDocument($orderId, $documentType);

                // 3. create the order based on request data + pdf
                $oYCube = new AsignYellowcubeCore();
                $aResponse = $oYCube->createYCCustomerOrder($aOrders);

                //4. save response in database if YC-response is successfull
                if ($aResponse['success']) {
                    $oModel->saveOrderResponseData($aResponse, $orderId, 'DC');
                }
            }

            // do not render a template when in service mode
            $this->Front()->Plugins()->ViewRenderer()->setNoRender(true);
        } catch(Exception $e) {
            $oLogs = Shopware()->Models()->getRepository("Shopware\CustomModels\AsignModels\Errorlogs\Errorlogs");
            $oLogs->saveLogsData('triggerYellowcubeAction', $e);            
        }
	}

	/**
     * Internal helper function which is used from the batch function and the createDocumentAction.
     * The batch function fired from the batch window to create multiple documents for many orders.
     * The createDocumentAction fired from the detail page when the user clicks the "create Document button"
     * @param $orderId
     * @param $documentType
     *
     * @return bool
     */
    private function createDocument($orderId, $documentType)
    {
        try{
            // set the array values to be sent...
            $aRenderer = array(
                'render'    => 'pdf',
                'preview'   => false
                );

            // if orderid sent in url?
            if ($this->Request()->getParam('orderId')) {
                $aRenderer['orderid'] = $this->Request()->getParam('orderId');
            } else {
                $aRenderer['orderid'] = $orderId;
            }

            // is orderid sent in url?
            if ($this->Request()->getParam('preview')) {
                $aRenderer['preview'] = true;
            }

            $deliveryDate = $this->Request()->getParam('deliveryDate', null);
            if (!empty($deliveryDate)) {
                $deliveryDate = new \DateTime($deliveryDate);
                $deliveryDate = $deliveryDate->format('d.m.Y');
            }

            $displayDate = $this->Request()->getParam('displayDate', null);
            if (!empty($displayDate)) {
                $displayDate = new \DateTime($displayDate);
                $displayDate = $displayDate->format('d.m.Y');
            }

            $document = Shopware_Components_Document::initDocument(
                $orderId,
                $documentType,
                array(
                    'netto'                   => (bool) $this->Request()->getParam('taxFree', false),
                    'bid'                     => $this->Request()->getParam('invoiceNumber', null),
                    'voucher'                 => $this->Request()->getParam('voucher', null),
                    'date'                    => $displayDate,
                    'delivery_date'           => $deliveryDate,
                    // Don't show shipping costs on delivery note #SW-4303
                    'shippingCostsAsPosition' => (int) $documentType !== 2,
                    '_renderer'               => $renderer,
                    '_preview'                => $this->Request()->getParam('preview', false),
                    '_previewForcePagebreak'  => $this->Request()->getParam('pageBreak', null),
                    '_previewSample'          => $this->Request()->getParam('sampleData', null),
                    '_compatibilityMode'      => $this->Request()->getParam('compatibilityMode', null),
                    'docComment'              => $this->Request()->getParam('docComment', null),
                    'forceTaxCheck'           => $this->Request()->getParam('forceTaxCheck', false)
                )
            );

            $document->render($aRenderer);

            if ($renderer == "html") exit; // Debu//g-Mode

            return true;
        } catch(Exception $e) {
            // ignore for now.. nothing but template missing error...
            //$oLogs = Shopware()->Models()->getRepository("Shopware\CustomModels\AsignModels\Errorlogs\Errorlogs");
            //$oLogs->saveLogsData('createDocument', $e);            
        }
    }
}
