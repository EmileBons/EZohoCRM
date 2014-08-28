<?php

/**
 * EZohoCRM extension for Yii framework
 *
 * API Reference Zoho CRM
 * https://www.zoho.com/crm/help/api/api-methods.html
 *
 * @author: Emile Bons <emile@emilebons.nl>
 * @link http://www.malvee.com
 * @link http://www.emilebons.nl
 * @copyright Copyright &copy; Emile Bons 2013
 * @license The MIT License
 * @category Yii
 * @package
 * @version 1.0
 */

class EZohoCRM {
    
    /*
     * Auth Token
     * 
     * An authentication token is required in order to be able to make use of the Zoho CRM
     * API. An authentication token can be obtained by using the generateAuthToken
     * function inside this class or by using the url
     * https://accounts.zoho.com/apiauthtoken/create?SCOPE=ZohoCRM/crmapi while being
     * logged in in Zoho CRM. You could harcode the authToken, obtain it from a config
     * file or obtain it from a database in the line below.
     */
    
    public $authToken = null;
    
    /*
     * Module
     * 
     * Defines the module which you want to use within the application.
     */
    
    public $module = self::MODULE_LEADS;
    
    /*
     * MODULE constants
     */
    
    const MODULE_ACCOUNTS = 'Accounts';
    const MODULE_COMPETITORS = 'Competitors';
    const MODULE_CONTACTS = 'Contacts';
    const MODULE_DASHBOARDS = 'Dashboards';
    const MODULE_EMAILS = 'Emails';
    const MODULE_FORECASTS = 'Forecasts';
    const MODULE_INTEGRATIONS = 'Integrations';
    const MODULE_INVOICES = 'Invoices';
    const MODULE_LEADS = 'Leads';
    const MODULE_POTENTIALS = 'Potentials';
    const MODULE_PRICE_BOOKS = 'PriceBooks';
    const MODULE_PRODUCTS = 'Products';
    const MODULE_PURCHASE_ORDERS = 'PurchaseOrders';
    const MODULE_QUOTES = 'Quotes';
    const MODULE_REPORTS = 'Reports';
    const MODULE_SALES_ORDERS = 'SalesOrders';
    const MODULE_USERS = 'Users';
    
    /*
     * USERTYPE constants
     */
    
    const USERTYPE_ALL_USERS = 'AllUsers';
    const USERTYPE_ACTIVE_USERS = 'ActiveUsers';
    const USERTYPE_DEACTIVE_USERS = 'DeactiveUsers';
    const USERTYPE_ADMIN_USERS = 'AdminUsers';
    const USERTYPE_ACTIVE_CONFIRMED_ADMINS = 'ActiveConfirmedAdmins';
    
    /*
     * SCOPE constants
     */
    
    const SCOPE = 'crmapi';
    const SCOPE_AUTH_TOKEN_REQUEST = 'ZohoCRM/crmapi';
    const VERSION = 2;
    
    /*
     * BASEURL constant
     */
    
    const BASE_URL = 'https://crm.zoho.com/crm/private/json/';
    
    /*
     * AUTH TOKEN REQUEST URL constant
     */
    
    const AUTH_TOKEN_REQUEST_URL = 'https://accounts.zoho.com/apiauthtoken/nb/create';
    
    /*
     * Parameter constants
     * 
     * These constants are used when calling methods in the API.
     */
    
    const ALL_COLUMNS = 'All';
    const SORT_ORDER_ASC = 'asc';
    const SORT_ORDER_DESC = 'desc';
    
    /**
     * Constructor
     * @param array $config_array  Use it to override default values for variables
     *
     */
    public function __construct($config_array = null)
    {

        Yii::import('application.extensions.EHttpClient.*');
        Yii::import('application.extensions.EHttpClient.adapter.*');

        if (!empty($config_array)) {
            foreach ($config_array as $key => $value) {
                $this->$key = $value;
            }
        }
        
    }

    
    private function zohoCrmApiCall($path, $method = EHttpClient::GET, 
        $getParameters = null, $postbody = null, $body_enctype = null)
    {
        $adapter = new EHttpClientAdapterCurl();

        $client = new EHttpClient($path, array(
            'maxredirects' => 2,
            'timeout' => 30,
            'adapter' => 'EHttpClientAdapterCurl'
        ));
        
        $client->setMethod($method);

        if (!empty($postbody))
            $client->setRawData($postbody, $body_enctype);
        
        $defaultGetParameters = array('SCOPE'=>self::SCOPE);
        
        if(!empty($this->authToken)) $defaultGetParameters['authtoken']=$this->authToken;
        
        if (!empty($getParameters)) $GET_Parameters = array_merge($defaultGetParameters, 
            $getParameters); 
        else
            $GET_Parameters = $defaultGetParameters;
        $client->setParameterGet($GET_Parameters);
        
        $curlOptions = array(CURLOPT_RETURNTRANSFER=>true, CURLOPT_FAILONERROR=>false,
            CURLOPT_SSL_VERIFYPEER=>false, CURLOPT_SSL_CIPHER_LIST=>'rsa_rc4_128_sha');
        
        $client->setAdapter($adapter);
        $adapter->setConfig(array('curloptions'=>$curlOptions));
        $response = $client->request();
        
        $response_obj = $response->getBody();
        $json = json_decode($response_obj);
        if(isset($json->response->error))
            throw new Exception('Error '.$json->response->error->code.': '.
                $json->response->error->message);
        
        
        
        return $response_obj;
    }
    
    /*
     * convertLead
     * 
     * You can use this method to convert lead to potential, account and contact. - 
     * See more at: https://www.zoho.com/crm/help/api/convertlead.html
     */
    
    public function convertLead($leadId, $createPotential, $assignTo,
        $notifyLeadOwner, $notifyNewEntityOwner, $potentialName = null,
        $closingDate = null, $potentialStage = null, $contactRole = null, $amount = null,
        $probability = null, $excludeNull = false, $version = self::VERSION, 
        $print = false) {
        
        $moduleBefore = $this->module; $this->module = self::MODULE_POTENTIALS;
        
        $rowNo1 = array(
            'createPotential' => $this->getBoolean($createPotential),
            'assignTo' => (string)$assignTo,
            'notifyLeadOwner' => $this->getBoolean($notifyLeadOwner),
            'notifyNewEntityOwner' => $this->getBoolean($notifyNewEntityOwner),
        );
        
        if(is_object($closingDate)) $closingDate = $closingDate->format('m/d/Y');
        
        $rowNo2 = array(
            'Potential Name' => (string)$potentialName,
            'Closing Date' => (string)$closingDate,
            'Potential Stage' => (string)$potentialStage,
            'Contact Role' => (string)$contactRole,
            'Amount' => (string)$amount,
            'Probability' => (string)$probability,
        );
        
        $xmlData = '<'.$this->module.'><row no="1">';
        
        foreach($rowNo1 as $key => $value)
            $xmlData .= '<option val="'.$key.'">'.$value.'</option>';
        $xmlData .= '</row>';
        if($createPotential) {
            $xmlData .= '<row no="2">';
            foreach($rowNo2 as $key => $value)
                $xmlData .= '<FL val="'.$key.'">'.$value.'</FL>';
            $xmlData .= '</row>';
        }
        $xmlData .= '</'.$this->module.'>';
        
        $path = self::BASE_URL.$moduleBefore.'/'.__FUNCTION__;
        
        $getParameters = array(
            'leadId' => (string)$leadId,
            'xmlData' => $xmlData,
            'newFormat' => $this->getNewFormat($excludeNull),
            'version' => $version,
        );
        
        $response = $this->zohoCrmApiCall($path, EHttpClient::GET, $getParameters);
        
        if($print) $this->print_response($response);
        
        else return json_decode($response);
        
        $this->module = $moduleBefore;
        
    }
    
    /*
     * deleteRecords
     * 
     * You can use this method to delete the selected record (you must specify unique ID 
     * of the record) and move to the recycle bin. - See more at: 
     * https://www.zoho.com/crm/help/api/deleterecords.html
     */
    
    public function deleteRecords($id, $print = false) {
        
        $path = self::BASE_URL.$this->module.'/'.__FUNCTION__;
        
        $getParameters = array('id' => $id);
        
        $response = $this->zohoCrmApiCall($path, EHttpClient::GET, $getParameters);
        
        if($print) $this->print_response ($response);
        
        else return json_decode($response);
        
    }
    
    /*
     * generateAuthToken
     * 
     * The Zoho CRM API is available in all editions of Zoho CRM. To use the API, you'll
     * require the Zoho CRM Authentication Token from your CRM account. Please make sure 
     * that you have the permission to access the API service. If you do not have 
     * permission, please contact your CRM administrator. - See more at: 
     * https://www.zoho.com/crm/help/api/using-authentication-token.html
     */
    
    public function generateAuthToken($usernameOrEmail, $password, $print = false)
    {
        $path = self::AUTH_TOKEN_REQUEST_URL;
        
        $getParameters = array(
            'SCOPE' => self::SCOPE_AUTH_TOKEN_REQUEST,
            'EMAIL_ID' => $usernameOrEmail,
            'PASSWORD' => $password,
        );
        
        $response = $this->zohoCrmApiCall($path, EHttpClient::GET, $getParameters);
        
        if($print)
            $this->print_response($response);
        else
            return $response;
        
    }
    
    /*
     * getBoolean
     * 
     * Returns a string for the given boolean.
     */
    
    private function getBoolean($boolean) { return ($boolean ? 'true' : 'false'); }
    
    /*
     * getCVRecords
     * 
     * You can use the getCVRecords method to fetch data with respect to the Custom View 
     * in Zoho CRM.
     * 
     * Important: Irrespective of the Zoho CRM Edition, you can send only 250 API requests 
     * / day. In each request you can fetch a maximum of 200 records. - See more at: 
     * https://www.zoho.com/crm/help/api/getcvrecords.html
     */
    
    public function getCVRecords($cvName, $fromIndex = 1, $toIndex = 20,
        $lastModifiedTime = null, $excludeNull = false, $version = self::VERSION,
        $print = false) {
        
        $path = self::BASE_URL.$this->module.'/'.__FUNCTION__;
        
        $getParameters = array(
            'cvName' => $cvName,
            'fromIndex' => $fromIndex,
            'toIndex' => $toIndex,
            'lastModifiedTime' => $lastModifiedTime,
            'newFormat' => $this->getNewFormat($excludeNull),
            'version' => $version
        );
        
        $response = $this->zohoCrmApiCall($path, EHttpClient::GET, $getParameters);
        
        if($print) $this->print_response ($response);
        
        else return json_decode($response);
        
    }
    
    /*
     * getEscapedValue
     * 
     * Returns the escaped value which can be used in the xmlData parameter.
     */
    
    private function getEscapedValue($value, $method) {
        if($value != htmlentities($value) && $method === EHttpClient::GET)
            $value = '<![CDATA['.htmlentities($value).']]>';
        elseif($value != htmlentities($value) && $method === EHttpClient::POST)
            $value = '<![CDATA['.$value.']]>';
        return $value;
    }
    
    /*
     * getFields
     * 
     * You can use the getFields method to fetch details of the fields available in a 
     * particular module. - See more at: https://www.zoho.com/crm/help/api/getfields.html
     */
    
    public function getFields($print = false) {
        
        $path = self::BASE_URL.$this->module.'/'.__FUNCTION__;
        
        $response = $this->zohoCrmApiCall($path, EHttpClient::GET);
        
        if($print) $this->print_response ($response);
        
        else return json_decode($response);
        
    }
    
    /*
     * getMyRecords
     * 
     * You can use the getMyRecords method to fetch data by the owner of the 
     * Authentication token specified in the API request. - See more at:
     * https://www.zoho.com/crm/help/api/getmyrecords.html
     */
    
    public function getMyRecords($columns = array(), $fromIndex = 1, $toIndex = 20, 
        $sortColumnString = null, $sortOrderString = self::SORT_ORDER_ASC,
        $lastModifiedTime = null, $excludeNull = false, $version = self::VERSION,
        $print = false) {
        
        return $this->getRecords($this->module, $columns, $fromIndex, $toIndex, 
            $sortColumnString, $sortOrderString, $lastModifiedTime, $excludeNull,
            $version, $print, true);
        
    }
    
    /*
     * getNewFormat
     * 
     * New format is an integer and can be either 1 or 2. 1 means that null values are
     * excluded, 2 means the opposite.
     */
    
    private function getNewFormat($excludeNull) { return ($excludeNull ? 1 : 2); }
    
    /*
     * getRecordById
     * 
     * You can use this method to retrieve individual records by record ID. - See more at: 
     * https://www.zoho.com/crm/help/api/getrecordbyid.html
     */
    
    public function getRecordById($id, $excludeNull = false, $version = self::VERSION,
        $print = false) {
        
        $path = self::BASE_URL.$this->module.'/'.__FUNCTION__;
        
        $getParameters = array(
            'id' => (string)$id,
            'newFormat' => $this->getNewFormat($excludeNull),
            'version' => $version
        );
        
        $response = $this->zohoCrmApiCall($path, EHttpClient::GET, $getParameters);
        
        if($print) $this->print_response ($response);
        
        else return json_decode($response);
        
    }
    
    /*
     * getRecords
     * 
     * You can use the getRecords method to fetch all users data specified in the API 
     * request. - See more at: https://www.zoho.com/crm/help/api/getrecords.html
     */
    
    public function getRecords($columns = array(), $fromIndex = 1, $toIndex = 20, 
        $sortColumnString = null, $sortOrderString = self::SORT_ORDER_ASC,
        $lastModifiedTime = null, $excludeNull = false, $version = self::VERSION,
        $print = false, $myRecords = false) {
        
        $path = self::BASE_URL.$this->module.'/'.
            ($myRecords ? 'getMyRecords' : __FUNCTION__);
        
        $getParameters = array(
            'selectColumns' => $this->getSelectColumns($columns),
            'fromIndex' => $fromIndex,
            'toIndex' => $toIndex,
            'sortColumnString' => $sortColumnString,
            'sortOrderString' => $sortOrderString,
            'lastModifiedTime' => $lastModifiedTime,
            'newFormat' => ($excludeNull ? 1 : 2),
            'version' => $version
        );
        
        $response = $this->zohoCrmApiCall($path, EHttpClient::GET, $getParameters);
        
        if($print) $this->print_response($response);
        
        else return json_decode($response);
        
    }
    
    /*
     * getRelatedRecords
     * 
     * You can use the getRelatedRecords method to fetch related records. - See more at: 
     * https://www.zoho.com/crm/help/api/getrelatedrecords.html
     */
    
    public function getRelatedRecords($parentModule, $id, $excludeNull = false,
        $fromIndex = 1, $toIndex = 20, $print = false) {
        
        $nonSupportedModules = array(self::MODULE_EMAILS, self::MODULE_COMPETITORS, 
            self::MODULE_INTEGRATIONS);
        
        if(in_array($this->module, $nonSupportedModules))
                
            throw new Exception("Module $this->module not supported for this function");
        
        $path = self::BASE_URL.$this->module.'/'.__FUNCTION__;
        
        $getParameters = array(
            'parentModule' => (string)$parentModule,
            'id' => (string)$id,
            'newFormat' => $this->getNewFormat($excludeNull),
            'fromIndex' => (string)$fromIndex,
            'toIndex' => (string)$toIndex,
        );
        
        $response = $this->zohoCrmApiCall($path, EHttpClient::GET, $getParameters);
        
        if($print) $this->print_response ($response);
        
        else return json_decode($response);
        
    }
    
    /*
     * getSearchRecords
     * 
     * You can use this method to search records by expressions of the selected columns. -
     * See more at: https://www.zoho.com/crm/help/api/getsearchrecords.html
     */
    
    public function getSearchRecords($selectColumns = array(), $searchCondition,
        $excludeNull = false, $fromIndex = 1, $toIndex = 20, $version = self::VERSION,
        $print = false) {
        
        $path = self::BASE_URL.$this->module.'/'.__FUNCTION__;
        
        $getParameters = array(
            'selectColumns' => $this->getSelectColumns($selectColumns),
            'searchCondition' => $searchCondition,
            'newFormat' => $this->getNewFormat($excludeNull),
            'fromIndex' => $fromIndex,
            'toIndex' => $toIndex,
            'version' => $version
        );
        
        $response = $this->zohoCrmApiCall($path, EHttpClient::GET, $getParameters);
        
        if($print) $this->print_response($response);
        
        else return json_decode($response);
        
    }
    
    /*
     * getSearchRecordsByPDC
     * 
     * You can use this method to search the values based on predefined columns. - 
     * See more at: https://www.zoho.com/crm/help/api/getsearchrecordsbypdc.html
     */
    
    public function getSearchRecordsByPDC($selectColumns = array(), $searchColumn,
        $searchValue, $excludeNull = false, $version = self::VERSION, $print = false) {
        
        $path = self::BASE_URL.$this->module.'/'.__FUNCTION__;
        
        $getParameters = array(
            'selectColumns' => $this->getSelectColumns($selectColumns),
            'searchColumn' => (string)$searchColumn,
            'searchValue' => (string)$searchValue,
            'newFormat' => $this->getNewFormat($excludeNull),
            'version' => $version,   
        );
        
        $response = $this->zohoCrmApiCall($path, EHttpClient::GET, $getParameters);
        
        if($print) $this->print_response($response);
        
        else return json_decode($response);
        
    }
    
    /*
     * getSelectColumns
     * 
     * Returns a string indicating which columns should be returned based on the
     * selectColumns input variable.
     */
    
    private function getSelectColumns($selectColumns) {
        
        return ($selectColumns === array() ? self::ALL_COLUMNS : 
            $this->module.'('.implode(',', $selectColumns).')');
        
    }
    
    /*
     * getUsers
     * 
     * You can use the getUsers method to get the list of users in your organization. - 
     * See more at: https://www.zoho.com/crm/help/api/getusers.html
     */
    
    public function getUsers($type, $excludeNull = false, $print = false) {
        
        $path = self::BASE_URL.self::MODULE_USERS.'/'.__FUNCTION__;
        
        $getParameters = array(
            'type' => (string)$type,
            'newFormat' => $this->getNewFormat($excludeNull), 
        );
        
        $response = $this->zohoCrmApiCall($path, EHttpClient::GET, $getParameters);
        
        if($print) $this->print_response($response);
        
        else return json_decode($response);
        
    }
    
    /*
     * insertRecords
     * 
     * You can use the insertRecords method to insert records into the required Zoho CRM 
     * module. - See more at: https://www.zoho.com/crm/help/api/insertrecords.html
     */
    
    public function insertRecords($records, $wfTrigger = false, $duplicateCheck = 1,
        $isApproval = false, $excludeNull = false, $version = self::VERSION,
        $print = false) {
        
        $path = self::BASE_URL.$this->module.'/'.__FUNCTION__;
        
        $postParameters = array(
            'xmlData' => $this->transformRecordsToXmlData($records, EHttpClient::POST),
            'wfTrigger' => (string)$wfTrigger,
            'duplicateCheck' => $duplicateCheck,
            'isApproval' => (string)$isApproval,
            'newFormat' => $this->getNewFormat($excludeNull),
            'version' => $version
        );
        
        $response = $this->zohoCrmApiCall($path, EHttpClient::POST, $postParameters);
        
        if($print) $this->print_response ($response);
        
        else return json_decode($response);
        
    }
    
    /*
     * transformRecordsToXmlData
     * 
     * Transform one or multiple records to XML Data. This function can, for example, be
     * used to format an array of Leads to XML Data in order to make the data ready for
     * the insertRecords function.
     */
    
    public function transformRecordsToXmlData($records, $method) {
        
        $modulesNotSupportedForMultipleInsert = array('Quotes', 'SalesOrders', 'Invoices',
            'PurchaseOrders');
        
        if(count($records) > 1 && in_array($this->module,
            $modulesNotSupportedForMultipleInsert))
                
            throw new Exception("Module $this->module does not support multiple inserts");
        
        if(count($records) > 100)
            
            throw new Exception('Only the first 100 records will be considered when inse'.
                'rting multiple records.');
        
        $xml = '<'.$this->module.'>';
        $rowNumber = 1;
        foreach($records as $record) {                   
            
            $xml .= '<row no="'.$rowNumber++.'">';
            foreach($record as $key => $value) {
                $value = $this->getEscapedValue($value, $method);            
                $xml .= '<FL val="'.$key.'">'.$value.'</FL>';
            }
                
                
                
            $xml .= '</row>';
        }
        $xml .= '</'.$this->module.'>';
        
        return $xml;
    }
    
    /*
     * updateRecords
     * 
     * You can use the updateRecords method to update or modify the records in Zoho CRM. - 
     * See more at: https://www.zoho.com/crm/help/api/updaterecords.html
     */
    
    public function updateRecords($id, $records, $wfTrigger = false, $excludeNull = false,
        $version = self::VERSION, $print = false) {
        
        $path = self::BASE_URL.$this->module.'/'.__FUNCTION__;
        
        $postParameters = array(
            'id' => (string)$id,
            'xmlData' => $this->transformRecordsToXmlData($records, EHttpClient::POST),
            'wfTrigger' => (string)$wfTrigger,
            'newFormat' => $this->getNewFormat($excludeNull),
            'version' => $version
        );
        
        $response = $this->zohoCrmApiCall($path, EHttpClient::POST, $postParameters);
        
        if($print) $this->print_response ($response);
        
        else return json_decode($response);
        
    }
    
    /*
     * updateRelatedRecords
     * 
     * You can use the updateRelatedRecords method to update records related to another 
     * record. - See more at: https://www.zoho.com/crm/help/api/updaterelatedrecords.html
     */
    
    public function updateRelatedRecords($relatedModule, $id, $records) {
        
        $path = self::BASE_URL.$this->module.'/'.__FUNCTION__;
        
        $getParameters = array(
            'relatedModule' => (string)$relatedModule,
            'id' => (string)$id,
            'xmlData' => $this->transformRecordsToXmlData($records, EHttpClient::GET),    
        );
        
        $response = $this->zohoCrmApiCall($path, EHttpClient::GET, $getParameters);
        
        return json_decode($response);
        
    }
    
    private function print_response($response)
    {
        echo '<pre>';
        print_r($response);
        echo('</pre>');
    }
    
}

?>
