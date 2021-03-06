<?php
/**
 * This file defines the plugin information and extensions
 *
 * PHP version 5
 *
 * @category  asign
 * @package   AsignYellowcube_v2.0_CE_5.1
 * @author    entwicklung@a-sign.ch
 * @copyright A-Sign
 * @license   http://www.a-sign.ch/
 * @version   2.0
 * @link      http://www.a-sign.ch/
 * @see       Shopware_Plugins_Backend_AsignYellowcube_Bootstrap
 * @since     File available since Release 1.0
 */

/**
* Defines plugin information
*
* @category A-Sign
* @package  AsignYellowcube_v2.0_CE_5.1
* @author   entwicklung@a-sign.ch
* @link     http://www.a-sign.ch
*/
class Shopware_Plugins_Backend_AsignYellowcube_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{
    /**
    * CRON default time interveal. Default = 5
    * @var integer
    */
    protected $iDefaultCronInterval = 5;

    /**
    * Array of CRONs created for the execution
    * @var array
    */
    protected $aCronEntries = array(
            'ActArtCron'    => 'Asign-YC Active articles',
            'InactArtCron'  => 'Asign-YC Inactive articles", "InactArtCron',
            'OrdCron'       => 'Asign-YC Prepayment Orders',
            'InventCron'    => 'Asign-YC Inventory", "InventCron',
        );

    /**
     * Array of shipping methods to be installed
     * @var array
     */
    protected $_aShippingCosts = array(
        'SPS_ECO'               => 'PostPac Economy',
        'SPS_ECO_SI'            => 'PostPac Economy mit Unterschrift (SI)',
        'SPS_PRI'               => 'PostPac Priority',
        'SPS_PRI_SI'            => 'PostPac Priority mit Unterschrift (SI)',
        'SPS_PRI_SI;AZ'         => 'PostPac Priority SI Abendzustellung',
        'SPS_PRI_SI;SA'         => 'PostPac Priority SI Samstagszustellung',
        'SPS_PICKUP_APOST'      => 'Abholung A-Post',
        'SPS_PICKUP_URGENT'     => 'International TNT',
        'SPS_PICKUP_INTPRI;GR'  => 'Abholung International Priority Gross',
        'SPS_PICKUP_INTPRI;MX'  => 'Abholung International Priority Maxi',
        'SPS_PICKUP_INTECO;GR'  => 'Abholung International Economy Gross',
        'SPS_PICKUP_INTECO;MX'  => 'Abholung International Economy Maxi',
        'SPS_INTPRI'            => 'Internationaler Kleinpaket-Brief Priority',
        'SPS_INTECO'            => 'Internationaler Kleinpaket-Brief Economy'
    );

    /**
     * Returns plugin info
     *
     * @return string
     *
     * @throws Exception
     */
    public function getInfo()
    {
        return array(
            'label'         => $this->getPluginInfo('label'),
            'class'         => $this->getPluginInfo('class'),
            'author'        => $this->getPluginInfo('author'),
            'copyright'     => $this->getPluginInfo('copyright'),
            'support'       => $this->getPluginInfo('support'),
            'version'       => $this->getPluginInfo('version'),
            'link'          => $this->getPluginInfo('link'),
       );
    }

    /**
     * After init event of the bootstrap class.
     *
     * The afterInit function registers the custom plugin models.
     */
    public function afterInit()
    {
        $this->Application()->Loader()->registerNamespace('Shopware\AsignYellowcube', $this->Path());
        $this->registerCustomModels();
    }

    /**
     * Returns the version of the plugin as a string
     *
     * @param $sParam
     *
     * @throws Exception
     * @return string
     */
    public function getPluginInfo($sParam)
    {
        $info = json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'plugin.json'), true);

        if ($info) {
            return $info[$sParam];
        } else {
            throw new Exception('The plugin has an invalid version file.');
        }
    }

    /**
     * Triggers the plugin installation
     *
     * @return null
     */
    public function install()
    {
        // subscibe the events now
        $this->subscribeEvent(
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_AsignYellowcube',
            'getBackendController'
        );

        $this->subscribeEvent(
            'Enlight_Controller_Dispatcher_ControllerPath_Widgets_AsignWidgetCube',
            'onGetWidgetController'
        );

        $this->subscribeEvent(
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout',
            'onPostDispatchCheckout'
        );

        $this->subscribeEvent(
            'Enlight_Controller_Action_PostDispatch_Frontend_Register',
            'onPostDispatchRegister'
        );

        $this->subscribeEvent(
            'Enlight_Controller_Action_PostDispatch_Frontend_Account',
            'onPostDispatchAccount'
        );

        $this->subscribeEvent(
            'Shopware_Components_Document::render::before',
            'beforeDocumentRender'
        );

        $this->subscribeEvent(
            'Enlight_Controller_Action_Frontend_Register_saveRegister',
            'afterSaveRegisterAction'
        );

        $this->createMenuItem(array(
            'label'         => $this->getPluginInfo('label'),
            'controller'    => $this->getPluginInfo('controller'),
            'class'         => $this->getPluginInfo('class'),
            'action'        => $this->getPluginInfo('action'),
            'active'        => 1,
            'parent'        => $this->Menu()->findOneBy(['label' => 'Einstellungen'])
        ));

        $this->ycubeCreateDispatch();
        $this->ycubeCreateConfiguration();
        $this->ycubeManageTableQueries();
        $this->ycubeUpdateSnippets();
        $this->ycubeTemplateEntry();

        /** CRONJOB Setup **/
        $this->subscribeEvent('Shopware_CronJob_InactArtCron', 'onRunInactArtCron');
        $this->subscribeEvent('Shopware_CronJob_ActArtCron', 'onRunActArtCron');
        $this->subscribeEvent('Shopware_CronJob_OrdCron', 'onRunOrdCron');
        $this->subscribeEvent('Shopware_CronJob_InventCron', 'onRunInventCron');

        //Cronjobname, Controllername, Interval, [aktiv]
        foreach ($this->aCronEntries as $action => $name) {
            $this->createCronJob($name, $action, $this->iDefaultCronInterval, true);
        }

        return array(
                'success' => true,
                'invalidateCache' => array('backend')
           );
    }

    /**
     * Defines path for the backend controller
     *
     * @param Enlight_Event_EventArgs $arguments
     *
     * @return path
     */
    public function getBackendController(Enlight_Event_EventArgs $args)
    {
        $this->Application()->Template()->addTemplateDir(
            $this->Path() . 'Views/'
        );

        $this->Application()->Snippets()->addConfigDir(
            $this->Path() . 'Snippets/'
        );

        $this->registerCustomModels();

        return $this->Path() . '/Controllers/Backend/AsignYellowcube.php';
    }

    /**
     * Defines path for the widget controller
     *
     * @param Enlight_Event_EventArgs $arguments
     *
     * @return path
     */
    public function onGetWidgetController(Enlight_Event_EventArgs $arguments)
    {
        $this->Application()->Template()->addTemplateDir(
            $this->Path() . 'Views/'
        );

        return $this->Path(). 'Controllers/Widgets/AsignWidgetCube.php';
    }

    /**
     * Event listener function called via the Enlight_Controller_Action_PostDispatch_Frontend_Checkout event.
     * The event is triggered when the reaches the thankyou page.
     *
     * @param Enlight_Event_EventArgs $arguments
     */
    public function onPostDispatchCheckout(Enlight_Event_EventArgs $arguments)
    {
        /**@var $controller Shopware_Controllers_Frontend_Checkout*/
        $controller = $arguments->getSubject();

        // defines the default shopware VIEWS directory.
        $view = $controller->View();
        $view->addTemplateDir(
            __DIR__ . '/Views'
        );

        $view->extendsTemplate('frontend/plugins/asign_yellowcube/checkout/index.tpl');
    }

    /**
     * Event listener function called via the Enlight_Controller_Action_PostDispatch_Frontend_Register event.
     *
     * @param Enlight_Event_EventArgs $arguments
     */
    public function onPostDispatchRegister(Enlight_Event_EventArgs $arguments)
    {
        /**@var $controller Shopware_Controllers_Frontend_Register*/
        $controller = $arguments->getSubject();

        // defines the default shopware VIEWS directory.
        $view = $controller->View();
        $view->addTemplateDir(
            __DIR__ . '/Views'
        );

        $view->extendsTemplate('frontend/plugins/asign_yellowcube/register/billing_fieldset.tpl');
    }

    /**
     * Event listener function called via the Enlight_Controller_Action_PostDispatch_Frontend_Account event.
     *
     * @param Enlight_Event_EventArgs $arguments
     */
    public function onPostDispatchAccount(Enlight_Event_EventArgs $arguments)
    {
        /**@var $controller Shopware_Controllers_Frontend_Account*/
        $controller = $arguments->getSubject();

        // defines the default shopware VIEWS directory.
        $view = $controller->View();
        $view->addTemplateDir(
            __DIR__ . '/Views'
        );

        // get EORI number
        $sUserId = $_SESSION['Shopware']['sUserId'];
        $view->sEoriNumber = Shopware()->Models()->getRepository("Shopware\CustomModels\AsignModels\Orders\Orders")->getOrderEoriNumber($sUserId);

        $view->extendsTemplate('frontend/plugins/asign_yellowcube/account/billing.tpl');
    }

    /**
     * Hook listener function called via the Shopware_Components_Document event.
     * The event is triggered when the reaches the thankyou page.
     *
     * @param Enlight_Event_EventArgs $arguments
     */
	 //change ro 20170122 correct argument type is Enlight_Hook_HookArgs
    public function beforeDocumentRender(Enlight_Hook_HookArgs $arguments)
    {
        try {
		$aRenderer = $arguments->get("_renderer");

        // if renderer is empty then?
        // call is made from BE
        if (empty($aRenderer) && $_REQUEST['temp']) {
            $aRenderer = array(
                'render'    => 'pdf',
                'preview'   => $_REQUEST['preview'],
                'orderid'   => $_REQUEST['orderId'],
            );
        }

            /** @extends Shopware_Components_Document **/
            require_once(Shopware()->AppPath("Plugins/Local/Backend/AsignYellowcube/Components/") . "PdfaDocument.php");

            $pdfaDoc = Enlight_Class::Instance('PdfaDocument');
            $pdfaDoc->pdfaRender($aRenderer);
        } catch(\Exception $e) {
            $oLogs = Shopware()->Models()->getRepository("Shopware\CustomModels\AsignModels\Errorlogs\Errorlogs");
            $oLogs->saveLogsData('beforeDocumentRender', $e);
        }
    }

    /**
     * Defines path for the backend controller
     *
     * @return null
     */
    public function ycubeManageTableQueries()
    {
        $this->ycubeAlterTable();
        $this->createYellowcubeTable('Product');
        $this->createYellowcubeTable('Orders');
        $this->createYellowcubeTable('Inventory');
        $this->createYellowcubeTable('Errorlogs');
    }

    /**
     * Creates Product table based on schema tool
     *
     * @param string $sSchema Name
     *
     * @return null
     */
    private function createYellowcubeTable($sSchema)
    {
        $sFinalSchema = "\\" . $sSchema . "\\" . $sSchema;
        $em = $this->Application()->Models();
        $tool = new \Doctrine\ORM\Tools\SchemaTool($em);

        $classes = array(
            $em->getClassMetadata("Shopware\CustomModels\AsignModels" . $sFinalSchema)
        );

        try {
            $tool->createSchema($classes);
        } catch (\Doctrine\ORM\Tools\ToolsException $e) {
            // ignore
        }
    }

    /**
     * Creates yellowcube dispatch/shipping methods
     *
     * @return null
     */
    private function ycubeCreateDispatch()
    {
        // activate shipping methods one by one...
        $idd = 100;
        foreach ($this->_aShippingCosts as $shipid => $shipping) {
            $iSql = "INSERT IGNORE INTO `s_premium_dispatch` SET `id` = '" . $idd . "', `name` = '" . $shipping . "', `description` = '" . $shipping . "', `comment` = '" . $shipid . "', `active` = 1";
            Shopware()->Db()->query($iSql);

            $idd = $idd + 1;
        }
    }

    /**
     * Create Configuration Method
     * Creates configuration form for all necessary details required for Yellowcube SOAP calls
     *
     * @return null
     */
    public function ycubeCreateConfiguration()
    {
        try {
            $ycForm = $this->Form();

            // Soap connection information only...
            $ycForm->setElement('button', 'main', array('label' => 'Main Configuration'));
            $ycForm->setElement('select', 'sYellowCubeMode',
                array(
                    'label' => 'Yellowcube Operating Mode',
                    'description' => 'Yellowcube Operating Modes: Test or Production. Before going Live set Mode to Production mode. Default value Test mode.',
                    'store' => array(
                        array('D', 'Development'),
                        array('T', 'Test'),
                        array('P', 'Production')
                   )
               )
           );
            $ycForm->setElement('text', 'sYellowCubeWsdlUrl',
                array(
                    'required'      => 1,
                    'label'         => 'WSDL Connection URL',
                    'description'   => 'Specify the WSDL Connection URL which is responsible for Yellowcube operations.',
                    'value'         => 'https://service-test.swisspost.ch/apache/yellowcube-int/?wsdl'
               )
           );
            $ycForm->setElement('text', 'sYellowCubeTransMaxTime',
                array(
                    'label' => 'Maximum waiting Time (in seconds)',
                    'value' => '120'
               )
           );

            // authentication information only
            $ycForm->setElement('button', 'authentication', array('label' => 'Authentication'));
            $ycForm->setElement('text', 'sYellowCubeDepositorNo',
                array(
                    'required'      => 1,
                    'label'         => 'Yellowcube Depositor No.',
                    'description'   => 'Specify Yellowcube Depositor Number or Customer Number. e.g. 0000054321, 0000010154.',
                    'value'         => '0000040309'
               )
           );

            $ycForm->setElement('text', 'sYellowCubeSender',
                array(
                    'required'      => 1,
                    'label'         => 'Sender identity',
                    'description'   => 'Set the sender identity for connection/Authentication. For Testing purpose YCTest has been used. Later Live sender details can be used.',
                    'value'         => 'Testshop07'
               )
           );

            $ycForm->setElement('text', 'sYellowCubeReceiver',
                array(
                    'required'      => 1,
                    'label'         => 'Receiver Identity',
                    'description'   => 'Currently default value YELLOWCUBE has been set. Can be modified during LIVE integration.',
                    'value'         => 'YELLOWCUBE'
               )
           );

            // Certificate informationb only
            $ycForm->setElement('button', 'Certificate', array('label' => 'Certificate Configuration'));
            $ycForm->setElement('select', 'blYellowCubeCertForAll',
                array(
                    'label' => 'Use Certificate for all modes',
                    'store' => array(
                        array('1', 'Yes'),
                        array('0', 'No')
                   )
               )
           );

            $ycForm->setElement('text', 'sYellowCubeCertFile',
                array(
                    'required'      => 1,
                    'label'         => 'Certificate Filename',
                    'description'   => 'Specify the YC Certificate filename placed under your Shop-root /cert folder.',
                    'value'         => '01-CLIENT-ASign.pem'
               )
           );

            // partner information
            $ycForm->setElement('button', 'partner', array('label' => 'Partner Information'));
            $ycForm->setElement('text', 'sYellowCubePartnerNo',
                array(
                    'required'      => 1,
                    'label'         => 'Partner Nummer',
                    'description'   => 'Set Partner Number. Used for sending Order to YC.',
                    'value'         => '0000300006'
               )
           );

            $ycForm->setElement('text', 'sYellowCubePType',
                array(
                    'required'      => 1,
                    'label'         => 'Partner Type',
                    'description'   => 'WE = Warenempfänger (Im Standard YellowCube wird nur eine Partnerrolle neben dem Aufgeber unterstützt.)',
                    'value'         => 'WE'
               )
           );

            $ycForm->setElement('text', 'sYellowCubePlant',
                array(
                    'required'      => 1,
                    'label'         => 'Plant ID',
                    'description'   => 'Plant is the place where the product is stored in the auto-store. Bearing ID as a work ID according to the profile distance dealer. e.g. Y001, Y004, Y010, etc.',
                    'value'         => 'Y007'
               )
            );

            // Article informations only...
            $ycForm->setElement('button', 'Article', array('label' => 'Article Configuration'));
            $ycForm->setElement('text', 'sYellowCubeQuantityISO',
                array(
                    'required'      => 1,
                    'label'         => 'Default Quantity (ISO)',
                    'description'   => 'Verkaufs-Mengeneinheit in ISO-Code. Werte gemäss gültiger, mit Kunde vereinbarter Verkaufsmengenheinheiten.',
                    'value'         => 'PCE'
               )
           );

            $ycForm->setElement('text', 'sYellowCubeAlternateUnitISO',
                array(
                    'required'      => 1,
                    'label'         => 'Alterantive Basismengen-Einheit',
                    'value'         => 'PCE'
               )
           );

            $ycForm->setElement('select', 'sYellowCubeEANType',
                array(
                    'label' => 'EANType',
                    'store' => array(
                        array('HE','Hersteller-EAN'),
                        array('HK','Hersteller-Kurz-EAN'),
                        array('I6','ITF-Code - 16stellig'),
                        array('IC','ITF-Code'),
                        array('IE','Instore-EAN (int. Vergabe mögl.)'),
                        array('IK','Instore-Kurz-EAN (int. Vergabe mögl.)'),
                        array('UC','UPC-Code'),
                        array('VC','Velocity-Code (int. Vergabe mögl.)')
                   )
               )
           );

            $ycForm->setElement('select', 'sYellowCubeNetWeightISO',
                array(
                    'label' => 'Default Nettogewicht (ISO)',
                    'store' => array(
                        array('GRM','Gramm [gr]'),
                        array('KGM','Kilogramm [kg]')
                   )
               )
           );

            $ycForm->setElement('select', 'sYellowCubeGrossWeightISO',
                array(
                    'label' => 'Default Bruttogewicht (ISO)',
                    'store' => array(
                        array('GRM','Gramm [gr]'),
                        array('KGM','Kilogramm [kg]')
                   )
               )
           );

            $ycForm->setElement('select', 'sYellowCubeLengthISO',
                array(
                    'label' => 'Länge (ISO)',
                    'store' => array(
                        array('CMT','Centimeter'),
                        array('MTR','Meter'),
                        array('MMT','Millimeter')
                   )
               )
           );

            $ycForm->setElement('select', 'sYellowCubeWidthISO',
                array(
                    'label' => 'Breite (ISO)',
                    'store' => array(
                        array('CMT','Centimeter'),
                        array('MTR','Meter'),
                        array('MMT','Millimeter')
                   )
               )
           );

            $ycForm->setElement('select', 'sYellowCubeHeightISO',
                array(
                    'label' => 'Höhe (ISO)',
                    'store' => array(
                        array('CMT','Centimeter'),
                        array('MTR','Meter'),
                        array('MMT','Millimeter')
                   )
               )
           );

            $ycForm->setElement('select', 'sYellowCubeVolumeISO',
                array(
                    'label' => 'Volumen (ISO)',
                    'store' => array(
                        array('CMQ','Kubik-Centimeter [cm3]'),
                        array('MTQ','Kubik-Meter [m3]')
                   )
               )
           );

            // Order informationb only...
            $ycForm->setElement('button', 'Order', array('label' => 'Order Configuration'));
            $ycForm->setElement('select', 'blYellowCubeOrderManualSend',
                array(
                    'label' => 'Manually send Order to Yellowcube?',
                    'description'   => 'Ja = Send order manually.<br />Nein = Send order immediately after completion.',
                    'store' => array(
                        array('1', 'Yes'),
                        array('0', 'No')
                   )
               )
           );
            $ycForm->setElement('select', 'sYellowCubeOrderDocumentsFlag',
                array(
                    'label' => 'Entscheid: Ausliefer-Dokumente werden mitgeliefert.',
                    'description'   => 'Ja = Dokument liegt vor und muss in Sendung mitgeliefert werden.<br>Nein = es liegen keine Lieferdokumente vor.',
                    'store' => array(
                        array('1', 'Yes'),
                        array('0', 'No')
                   )
               )
           );

            $ycForm->setElement('select', 'sYellowCubeDocMimeType',
                array(
                    'label' => 'Document MIME Type',
                    'description'   => 'MIME-Typ als Extention des Lieferbeleges. [pdf|pcl] Inhalt des Streams muss mit der Extention übereinstimmen.',
                    'store' => array(
                        array('pdf', 'PDF'),
                        array('pcl', 'PCL')
                   )
               )
           );

            $ycForm->setElement('select', 'sYellowCubeDocType',
                array(
                    'label' => 'Typ des Lieferbeleges',
                    'store' => array(
                        array('LS', 'Lieferschein'),
                        array('BL', 'Bulletin/Bordereau de livraison'),
                        array('BC', 'Bolla di Consegna'),
                        array('DN', 'Delivery note'),
                        array('IV', 'Invoice'),
                        array('RG', 'Rechnung'),
                        array('FA', 'Facture/Fatura'),
                        array('ZS', 'Zahlschein'),
                        array('BP', 'Bulletin de payement'),
                        array('BV', 'bollettino di versamento'),
                        array('PF', 'Payment form')
                   )
               )
           );

            // CRON JOB informations only...
            $ycForm->setElement('button', 'Cronjob', array('label' => 'CRON Job Configuration'));
            $ycForm->setElement('select', 'sCronArtFlag',
                array(
                    'label' => 'Default Article Mode',
                    'required'  => 1,
                    'store' => array(
                        array('I','Insert Article'),
                        array('U','Update Article'),
                        array('D','Deactivate Article')
                   )
               )
           );
            $ycForm->setElement('text', 'sYellowCubeCronHash',
                array(
                    'required'      => 1,
                    'label'         => 'Cron-job Hash value',
                    'description'   => 'Security measure to make sure the script is not called anonymously without Hash Tag',
                    'value'         => 'asignsw007'
               )
           );

            // Developer mode informations only...
            $ycForm->setElement('button', 'Developer', array('label' => 'Developer mode'));
            $ycForm->setElement('text', 'sYellowCubeNotifyEmail',
                array(
                    'required'      => 0,
                    'label'         => 'Developer Email-address',
                    'description'   => 'This should be used only during Development or debugging process. YC XML Request and Response will be sent on this email address.',
                    'value'         => ''
               )
           );
        } catch (\Exception $e) {
            throw new Exception('<b>Fehler beim erstellen des Einstellungsformulars</b><br />' . $e->getMessage());
        }
    }

    /**
     * Alter existing tables as per Queries...
     *
     * @return null
     */
    public function ycubeAlterTable()
    {
        // check if the `tariff` already exists
        if (!$this->isColumnExistsInTable('s_articles', 'tariff')) {
            Shopware()->Db()->query("ALTER TABLE `s_articles` ADD COLUMN `tariff` CHAR(11) NOT NULL DEFAULT ''");
        }

        // check if the `tara` already exists
        if (!$this->isColumnExistsInTable('s_articles', 'tara')) {
            Shopware()->Db()->query("ALTER TABLE `s_articles` ADD COLUMN `tara` DOUBLE NOT NULL DEFAULT 0.0");
        }

        // check if the `origin` already exists
        if (!$this->isColumnExistsInTable('s_articles', 'origin')) {
            Shopware()->Db()->query("ALTER TABLE `s_articles` ADD COLUMN `origin` CHAR(10) NOT NULL");
        }

        // check if the `tariff` already exists
        if (!$this->isColumnExistsInTable('s_order_details', 'tariff')) {
            Shopware()->Db()->query("ALTER TABLE `s_order_details` ADD COLUMN `tariff` CHAR(11) NOT NULL DEFAULT ''");
        }

        // check if the `tara` already exists
        if (!$this->isColumnExistsInTable('s_order_details', 'tara')) {
            Shopware()->Db()->query("ALTER TABLE `s_order_details` ADD COLUMN `tara` DOUBLE NOT NULL DEFAULT 0.0");
        }

        // check if the `origin` already exists
        if (!$this->isColumnExistsInTable('s_order_details', 'origin')) {
            Shopware()->Db()->query("ALTER TABLE `s_order_details` ADD COLUMN `origin` CHAR(10) NOT NULL");
        }
    }

    /**
     * Update snippet definitions for the custom field
     *
     * @return null
     */
    public function ycubeUpdateSnippets()
    {
        $aSnippets = array(
            "UPDATE `s_core_snippets` SET `value` = 'EORI No.' WHERE `name` = 'billing/text_1_label'",
            "UPDATE `s_core_snippets` SET `value` = 'EORI Number for customer' WHERE `name` = 'billing/text_1_support'",
            "UPDATE `s_core_snippets` SET `value` = 'This field is also visible during Registration and Account Edit form on Shop Front-end.' WHERE `name` = 'billing/text_1_help_text'",
            "UPDATE `s_core_snippets` SET `value` = 'Specify EORI No.' WHERE `name` = 'billing/text_1_help_title'"
        );

        //run the query
        foreach ($aSnippets as $query) {
            Shopware()->Db()->query($query);
        }
    }

    /**
     * Checks if the column exists in the table mentioned
     *
     * @param string $sTable  Table name where to check?
     * @param string $sColumn Table name what to check?
     *
     * @return true
     */
    protected function isColumnExistsInTable($sTable, $sColumn)
    {
        $sResult = Shopware()->Db()->fetchAll("SHOW COLUMNS FROM `" . $sTable . "` LIKE '" . $sColumn . "'");
        return !empty($sResult) ? true : false;
    }

    /**
     * Make Yellocube template entry
     *
     * @return null
     */
    public function ycubeTemplateEntry()
    {
        Shopware()->Db()->query("INSERT IGNORE INTO `s_core_documents` SET `id` = 5, `name` = 'Yellowcube', `template` = 'index_yc.tpl', `numbers` = 'ycdoc', `left` = '25', `right` = '10', `top` = '20', `bottom` = '20', `pagebreak` = '10'");
    }

    /**
     * called if the active articles cronjob is triggered
     *
     * @param Shopware_Components_Cron_CronJob $job
     * @return void
     */
    public function onRunActArtCron(Shopware_Components_Cron_CronJob $job)
    {
        $this->doExecuteCron('art', 'ax');
    }

    /**
     * called if the in-active articles cronjob is triggered
     *
     * @param Shopware_Components_Cron_CronJob $job
     * @return void
     */
    public function onRunInactArtCron(Shopware_Components_Cron_CronJob $job)
    {
        $this->doExecuteCron('art', 'ix');
    }

    /**
     * called if the orders cronjob is triggered
     *
     * @param Shopware_Components_Cron_CronJob $job
     * @return void
     */
    public function onRunOrdCron(Shopware_Components_Cron_CronJob $job)
    {
        $this->doExecuteCron('ord', 'pp');
    }

    /**
     * called if the inventory cronjob is triggered
     *
     * @param Shopware_Components_Cron_CronJob $job
     * @return void
     */
    public function onRunInventCron(Shopware_Components_Cron_CronJob $job)
    {
        $this->doExecuteCron('inv');
    }

     /**
     * Run the article cron as per requirement...
     * Types: articles, orders and inventory
     *
     * @param string $sType - Type of CRON request
     * @param string $sMode - Mode of article cron
     *
     * @return void
     */
    private function doExecuteCron($sType, $sMode)
    {
        $sFlag = Shopware()->Plugins()->Backend()->AsignYellowcube()->Config()->sCronArtFlag;
        $cronResource = new \Shopware\AsignYellowcube\Components\Api\AsignYellowcubeCron();

        // trigger based on conditions?
        switch ($sType) {
            case 'art': $cronResource->autoInsertArticles($sMode, $sFlag, true);
            break;

            case 'ord': $cronResource->autoSendYCOrders($sMode, true);
            break;

            case 'inv': $cronResource->autoFetchInventory(true);
            break;

            default: $cronResource->autoInsertArticles('xx', $sFlag, true); // send all articles
                     $cronResource->autoSendYCOrders(null, true); // send all orders
            break;
        }
    }
}
