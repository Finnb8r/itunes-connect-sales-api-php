<?php

/**
 * @author Finnbar Roelants 2016
 * Check out https://help.apple.com/itc/appsreporterguide/#/ for more information about the possible requests
 *
 */
class iTunesSalesApi
{

    const ITUNES_CONNECT_SALES_ENDPOINT   = 'https://reportingitc-reporter.apple.com/reportservice/sales/v1';
    const ITUNES_CONNECT_FINANCE_ENDPOINT = 'https://reportingitc-reporter.apple.com/reportservice/finance/v1';

    const REPORT_TYPE_SALES = 'Sales';
    const SUBTYPE_SALES_SUMMARY = 'Summary';
    const SUBTYPE_SALES_OPTIN = 'Opt-In';


    const REPORT_TYPE_SUBSCRIPTION = 'Subscription';
    const SUBTYPE_SUBSCRIPTION_SUMMARY = 'Summary';

    const REPORT_TYPE_SUBSCRIPTION_EVENT = 'Subscription Event';
    const SUBTYPE_SUBSCRIPTION_EVENT_SUMMARY = 'Summary';

    const REPORT_TYPE_NEWSSTAND = 'Newsstand';
    const SUBTYPE_NEWSSTAND_DETAILED = 'Detailed';


    const REPORT_DATETYPE_DAILY = 'Daily';
    const REPORT_DATETYPE_WEEKLY = 'Weekly';
    const REPORT_DATETYPE_MONTHLY = 'Monthly';
    const REPORT_DATETYPE_YEARLY = 'Yearly';

    const DATE_FORMART_DAILY = 'Ymd';   //YYYYMMDD
    const DATE_FORMART_WEEKLY = 'Ymd';  //YYYYMMDD, the day used is the Sunday that week ends
    const DATE_FORMART_MONTHLY = 'Ym';  //YYYYMM
    const DATE_FORMART_YEARLY = 'Y';    //YYYY



    const REPORT_MODE_ALL          = 'A';
    const REPORT_MODE_EARNING_ONLY = 'B';
    
    const URL_PARAM_SALES_REPORT   = "Sales.getReport";
    const URL_PARAM_SALES_VENDORS  = "Sales.getVendors";
    const URL_PARAM_SALES_ACCOUNTS = "Sales.getAccounts";

    /**
     * Show non critical errors, if set to false, you can get errors  by calling getSoftErrors()
     *
     * @var boolean
     */
    public $throwErrors = false;

    /**
     * Errors
     *
     * @var array
     */
    public $errors = array();

    /**
     * Force refresh (force load distant server)
     *
     * @var boolean
     */
    private $_forceRefresh = false;


    /**
     * Force refresh (force load distant server)
     *
     * @var boolean
     */
    private $_reportMode = self::REPORT_MODE_ALL;

    /**
     * iTunes Connect login
     *
     * @var string
     */
    private $_userName;


    /**
     * iTunes Connect password
     *
     * @var string
     */
    private $_password;


    /**
     * iTunes Connect vendor
     *
     * @var string
     */
    private $_vendor;


    /**
     * Report possibilities
     *
     * @var array
     */
    private $_possibleReports;


    /**
     * Current report type
     *
     * @var string
     */
    private $_reportType;

    /**
     * Current report sub type
     *
     * @var string
     */
    private $_reportSubType;

    /**
     * Current report date type
     *
     * @var string
     */
    private $_reportDateType;

    /**
     * Current report date
     *
     * @var string
     */
    private $_reportDate;

    /**
     * Path to which the report should be saved
     *
     * @var string
     */
    private $_outputFolder;
    
    /**
     * URL the request will be made on
     *
     * @var boolean
     */
    private $_endpoint     = self::ITUNES_CONNECT_SALES_ENDPOINT;


    /**
     * The URL params for sales (report,vendors or accounts
     *
     * @var boolean
     */
    private $_queryMode    = self::URL_PARAM_SALES_REPORT;

	 /**
     * Disable SSL checks
     *
     * @var boolean
     */
    public $disableCurlSSL = false;

    /**
     * iTunesSalesApi constructor.
     * @param null $login
     * @param null $password
     * @param null $vendor
     */
    public function __construct($login = null, $password = null, $vendor = null)
    {
        if($login != null){
            $this->_userName = $login;
        }
        if($password != null){
            $this->_password = $password;
        }
        if($vendor != null){
            $this->_vendor = $vendor;
        }

        $this->_setPossibilities();
    }


    /**
     * set login (iTunes connect user)
     *
     * @param string $login
     * @return iTunesSalesApi
     */
    public function setLogin($login)
    {
        $this->_userName = $login;
        return $this;
    }

    /**
     * set password (iTunes connect password)
     *
     * @param string $password
     * @return iTunesSalesApi
     */
    public function setPassword($password)
    {
        $this->_password = $password;
        return $this;
    }

    /**
     * set vendor : for more information on getting the vendor, check out the github project
     *
     * @param string $vendor
     * @return iTunesSalesApi
     */
    public function setVendor($vendor)
    {
        $this->_vendor = $vendor;
        return $this;
    }


    /**
     * setReportModeAll (default)
     * API result will count and return only sales (non free items)
     * @return iTunesSalesApi
     */
    public function setReportModeAll()
    {
        $this->_setReportMode(self::REPORT_MODE_ALL);
        return $this;
    }


    /**
     * setReportModeEarningsOnly
     * API result will count all sales (free app downloads as well)
     * @return iTunesSalesApi
     */
    public function setReportModeEarningsOnly()
    {
        $this->_setReportMode(self::REPORT_MODE_EARNING_ONLY);
        return $this;
    }


    /**
     * set use cache
     *
     * @param boolean $useCache
     * @return iTunesSalesApi
     */
    public function setUseCache($useCache)
    {
        $this->_forceRefresh = !$useCache;
        return $this;
    }

    /**
     * set folder where reports will be saved
     *
     * @param string $folder name where we want to save reports to
     * @return iTunesSalesApi
     */
    public function setFolder($folder)
    {
        //check if a folder is specified
        $folder = trim($folder);
        if(strlen($folder) == 0){
            $this->_returnError("Please specify a folder - not saving locally");
        }

        //Check if it ends with a slash, if not, add it.
        if(!$this->endsWith("/",$folder)){
            $folder = $folder."/";
        }

        //Check if folder exists
        if(!is_dir($folder)){
            $this->_returnError("Folder $folder does not exist - not saving locally");
        }

        //Check if we have write permissions in this folder
        $tmpFile = "wr_test_".md5(time()).".txt";
        if(!@file_put_contents($folder.$tmpFile,"test")){
            $this->_returnError("We do not have write permission in $folder - not saving locally");
        }

        //All good, remove the temp file
        @unlink($folder.$tmpFile);

        $this->_outputFolder = $folder;
        return $this;
    }
    
    /**
     * 
     * @return array|bool
     */
    public function getVendors()
    {
        $this->_queryMode = self::URL_PARAM_SALES_VENDORS;
        return $this->_executeRequest();
    }

	/**
     * 
     * @return array|bool
     */
    public function getAccounts()
    {
        $this->_queryMode = self::URL_PARAM_SALES_ACCOUNTS;
        return $this->_executeRequest();
    }

    /**
     * @param null $date
     * @return array|bool
     */
    public function getSalesDailyReport($date = null)
    {
        return $this->_getSalesReport($date,self::REPORT_DATETYPE_DAILY);
    }

    /**
     * @param null $date
     * @return array|bool
     */
    public function getSalesWeeklyReport($date = null)
    {
        return $this->_getSalesReport($date,self::REPORT_DATETYPE_WEEKLY);
    }

    /**
     * @param null $date
     * @return array|bool
     */
    public function getSalesMonthlyReport($date = null)
    {
        return $this->_getSalesReport($date,self::REPORT_DATETYPE_MONTHLY);
    }

    /**
     * @param null $date
     * @return array|bool
     */
    public function getSalesYearlyReport($date = null)
    {
        return $this->_getSalesReport($date,self::REPORT_DATETYPE_YEARLY);
    }

    /**
     * @return bool
     */
    public function hasErrors(){
        return count($this->errors) > 0;
    }

    /**
     * @return string
     */
    public function getErrorsAsString(){
        return implode("\n",$this->errors);
    }

    /**
     * @return array
     */
    public function listPossibleReports()
    {
        return $this->_possibleReports;
    }

    /**
     * set report mode
     *
     * @param $mode $string iTunesSalesApi::REPORT_MODE_ALL or iTunesSalesApi::REPORT_MODE_EARNING_ONLY
     * @return iTunesSalesApi
     */
    public function _setReportMode($mode)
    {
        if($mode == self::REPORT_MODE_ALL || $mode == self::REPORT_MODE_EARNING_ONLY){
            $this->_reportMode  = $mode;
        }
        return $this;
    }

    /**
     * @return array
     */
    private function _setPossibilities()
    {
        $this->_possibleReports = array(

            self::REPORT_TYPE_SALES => array(
                self::SUBTYPE_SALES_SUMMARY => array(
                    self::REPORT_DATETYPE_DAILY => self::DATE_FORMART_DAILY,
                    self::REPORT_DATETYPE_WEEKLY => self::DATE_FORMART_WEEKLY,
                    self::REPORT_DATETYPE_MONTHLY => self::DATE_FORMART_MONTHLY,
                    self::REPORT_DATETYPE_YEARLY => self::DATE_FORMART_YEARLY
                ),
                self::SUBTYPE_SALES_OPTIN => array(
                    self::REPORT_DATETYPE_WEEKLY => self::DATE_FORMART_WEEKLY
                )
            ),

            self::REPORT_TYPE_SUBSCRIPTION_EVENT => array(
                self::SUBTYPE_SUBSCRIPTION_EVENT_SUMMARY => array(
                    self::REPORT_DATETYPE_DAILY => self::DATE_FORMART_DAILY
                )
            ),

            self::REPORT_TYPE_SUBSCRIPTION => array(
                self::SUBTYPE_SUBSCRIPTION_SUMMARY => array(
                    self::REPORT_DATETYPE_DAILY => self::DATE_FORMART_DAILY
                )
            ),

            self::REPORT_TYPE_NEWSSTAND => array(
                self::SUBTYPE_NEWSSTAND_DETAILED => array(
                    self::REPORT_DATETYPE_DAILY => self::DATE_FORMART_DAILY,
                    self::REPORT_DATETYPE_WEEKLY => self::DATE_FORMART_WEEKLY
                )
            )

        );
        return $this->_possibleReports;
    }

    /**
     * @param $date
     * @param $dateType
     * @return array|bool
     */
    private function _getSalesReport($date, $dateType)
    {
   		
   		//Set the adequate params
        $this->_queryMode = self::URL_PARAM_SALES_REPORT;
    
    	//Reset the errors
    	$this->errors 			= array();
    
        //Check if we have the required params
        $this->_checkParams();

        //Set the variables
        $this->_reportType      = self::REPORT_TYPE_SALES;
        $this->_reportSubType   = self::SUBTYPE_SALES_SUMMARY;
        $this->_reportDateType  = $dateType;
        $this->_reportDate      = null;

        if($date != null){

            if(strlen($date) == strlen(date($this->_possibleReports[$this->_reportType][$this->_reportSubType][$this->_reportDateType]))){

                $testDate = null;
                $year     = substr($date,0,4);

                if($dateType == self::REPORT_DATETYPE_YEARLY){
                    $testDate = date('Y',strtotime($year."-01-01"));
                }else if($dateType == self::REPORT_DATETYPE_MONTHLY){
                    $month = substr($date,4,2);
                    $testDate = date('Ym',strtotime($year."-".$month."-01"));
                }
                else {
                    $month = substr($date,4,2);
                    $day   = substr($date,6,2);
                    $testDate = date('Ymd',strtotime($year."-".$month."-".$day));
                }

                if($testDate && $testDate == $date){
                    $this->_reportDate = $date;
                }else{
                    $this->_returnError("Specified date is not a valid value, using default (yesterday)");
                }
            }else{
                $this->_returnError("Specified date is not a valid value, using default (yesterday)");
            }
        }

        if($this->_reportDate == null){
            //Ask for yesterday's report by default
            $this->_reportDate = date($this->_possibleReports[self::REPORT_TYPE_SALES][self::SUBTYPE_SALES_SUMMARY][$dateType],($dateType == self::REPORT_DATETYPE_DAILY ? strtotime("yesterday") : time()));
        }

        //Need to set it to a sunday (od the previous week)
        if($dateType == self::REPORT_DATETYPE_WEEKLY)
        {
            $monday = strtotime('last week monday', strtotime($this->_reportDate));
            $sunday = strtotime('+6 days', $monday);
            $this->_reportDate = date($this->_possibleReports[self::REPORT_TYPE_SALES][self::SUBTYPE_SALES_SUMMARY][$dateType],$sunday);
        }


        //do the request with Apple
        return $this->_executeRequest();

    }

    /**
     *
     */
    private function _checkParams()
    {
        if($this->_userName == null){
            $this->_returnError('Please specify a username before attempting to fetch any reports',true);
        }
        if($this->_password == null){
            $this->_returnError('Please specify a password before attempting to fetch any reports',true);
        }
        if($this->_vendor == null){
            $this->_returnError('Please specify a vendor before attempting to fetch any reports',true);
        }
    }

    /**
     * @param string $message
     * @param bool $critical
     */
    private function _returnError($message = "unknown", $critical = false)
    {
        if($this->throwErrors || $critical){
            throw new RunTimeException($message);
        }
        else{
            $this->errors[] = $message;
        }
    }


    /**
     * @return array|bool
     */
    private function _executeRequest()
    {

        $fileName = strtoupper(substr($this->_reportType,0,1))."_".strtoupper(substr($this->_reportSubType,0,1))."_".strtoupper(substr($this->_reportDateType,0,1))."_".$this->_vendor."_".$this->_reportDate.".txt";

        //Check if the file exists, return it if it's the case (only for the reports)
        if($this->_outputFolder != null && $this->_queryMode == self::URL_PARAM_SALES_REPORT){
            if(file_exists($this->_outputFolder.$fileName) && !$this->_forceRefresh){
                return $this->_parseSalesReport($this->_outputFolder.$fileName);
            }
        }
        
        //Build the query input
        $queryInput = "[p=Reporter.properties, ".$this->_queryMode;
        if($this->_queryMode == self::URL_PARAM_SALES_REPORT){
            $queryInput.=", ".$this->_vendor.",".$this->_reportType.",".$this->_reportSubType.",".$this->_reportDateType.",".$this->_reportDate;
        }
        $queryInput.="]";


        //Build request parameters
        $allParams  = array(
            "userid"=> $this->_userName,
            "password"=> $this->_password,
            "version"=> "1.0",
            "mode"=> "Normal",
            "queryInput"=> $queryInput
        );


        $jsonParams = "jsonRequest=".json_encode($allParams);

        //Do the cURL request
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->_endpoint);
        curl_setopt($curl, CURLOPT_HEADER, 1);
         curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/x-www-form-urlencoded'
        ));
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_VERBOSE, '1');
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonParams);
        
        if($this->disableCurlSSL){
        	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }

        //Execute the request
        $return = curl_exec($curl);
        $info   = curl_getinfo($curl);
        $code   = $info["http_code"];
        $cerror = curl_error($curl);
        
        curl_close($curl);

        $header_size = $info["header_size"];
        $header = substr($return, 0, $header_size);
        $body = substr($return, $header_size);

		//Accounts and vendors
        if($this->_queryMode != self::URL_PARAM_SALES_REPORT)
        {
            if($code == 200){
                $values = explode("\n",$body);
                $end_item = end($values);
                if (empty($end_item)) {
                    array_pop($values);
                }
                return $values;
            }else{
                $this->_returnError($body);
                return false;
            }
        }
		
		//Else, reports
		$curlHeaders   = $this->_curlHeadersAsArray($header);
		
		//Some error (issue #1 by FranRomero)
		if(!is_array($curlHeaders) || count($curlHeaders) == 0)
		{	
			//Print the cUrl error
			$this->_returnError("Unknown error has occured : ".$cerror,true);
			return false;
		}
		
		
        $headerAsArray = $curlHeaders[0];

        if(isset($headerAsArray["filename"])){
            //All good
            if($this->_outputFolder != null){
                //save it
                file_put_contents($this->_outputFolder.$fileName,$this->agzdecode($body));
                $name = $this->_outputFolder.$fileName;
            }else{
                $name = $this->_saveTemporarily($fileName,$this->agzdecode($body));
            }
            return $this->_parseSalesReport($name);

        }

        if(isset($headerAsArray["ERRORMSG"])){
            $this->_returnError($headerAsArray["ERRORMSG"],true);
            return false;
        }
        //No error message, no exception, no report...
        $this->_returnError("Unknown error has ocured (http code ".$code.", content : ".$body." )",true);
        return false;
    }

    /**
     * @param $name
     * @param $content
     * @return string
     */
    private function _saveTemporarily($name, $content)
    {
        $file = DIRECTORY_SEPARATOR .
            trim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) .
            DIRECTORY_SEPARATOR .
            ltrim($name, DIRECTORY_SEPARATOR);

        file_put_contents($file, $content);

        register_shutdown_function(function() use($file) {
            unlink($file);
        });

        return $file;
    }

    /**
     * @param $file
     * @return array
     */
    private function _parseSalesReport($file)
    {
   
		$fp = @fopen($file, 'r');
		if($fp === FALSE)
		{
			$this->_returnError("Unable to open file ".$file,true);
            return false;
		}
		
        $key_sku		 = 2;
        $key_earning 	 = 8;
        $key_beginDate 	 = 9;
        $key_endDate 	 = 10;
        $key_currency 	 = 13;
        $key_fullPrice   = 15;
        $key_units		 = 7;


        $head 			 = array();
        $sales			 = array();
        $revenues 		 = array();

        $earliestDate    = 0;
        $latestDate      = 0;

        $delimiter       =  "\t";
        $nb_sales 	     = 0;

        $nb_downloads    = 0;

        $row = 0;
        while ( !feof($fp) )
        {

            $line = fgets($fp, 2048);

            $data = str_getcsv($line, $delimiter);

            if($row == 0){
                $head = $data;
            }
            else{

				if(isset($data[$key_beginDate]) && isset($data[$key_endDate])){
                	$sDate = date("Ymd",strtotime($data[$key_beginDate]));
                	$eDate = date("Ymd",strtotime($data[$key_endDate]));


               		if(($sDate < $earliestDate && $sDate !=19700101)  || $earliestDate == 0){
                    	$earliestDate = $sDate;
                	}
                	if(($eDate > $latestDate && $eDate !=19700101) || $latestDate == 0){
                    	$latestDate = $eDate;
               	 	}
               	 }

                if(isset($data[$key_fullPrice]) && $data[$key_fullPrice] > 0){

                    $price 		= $data[$key_fullPrice];
                    $earnings	= $data[$key_earning];
                    $currency   = $data[$key_currency];

                    if($currency != ""){

                        $s = array();
                        foreach($head as $k => $v){
                            $s[$v] = $data[$k];
                        }
                        $sales[] = $s;

                        if(!isset($revenues[$currency])){
                            $revenues[$currency] = array();
                            $revenues[$currency]["turnover"] = 0;
                            $revenues[$currency]["earnings"] = 0;
                            $revenues[$currency]["sales"] 	 = 0;
                        }
                        $revenues[$currency]["turnover"]+=$price;
                        $revenues[$currency]["earnings"]+=$earnings;
                        $revenues[$currency]["sales"] += $data[$key_units];
                        $nb_sales += $data[$key_units];
                    }
                }else{
                    if($this->_reportMode == self::REPORT_MODE_ALL){
                        if(isset($data[$key_units])){
                        	
                        	$nb_downloads += $data[$key_units];
                        	$s = array();
                        	foreach($head as $k => $v){
                           	 	$s[$v] = $data[$k];
                       		}
                        	$sales[] = $s;
                        }
                    }
                }
            }

            $row++;

        }

        fclose($fp);

        $final = array();

        $final["report_start_date"] = $earliestDate;
        $final["report_end_date"]   = $latestDate;
        $final["number_sales"]      = $nb_sales;
        $final["app_downloads"]     = $nb_downloads;
        $final["revenues"] 		    = $revenues;
        $final["details"] 		    = $sales;

        return($final);
    }


    /**
     * @param $haystack
     * @param $needle
     * @return bool
     */
    protected function startsWith($haystack, $needle) {
        // search backwards starting from haystack length characters from the end
        return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
    }

    /**
     * @param $haystack
     * @param $needle
     * @return bool
     */
    protected function endsWith($haystack, $needle) {
        // search forward starting from end minus needle length characters
        return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
    }

    /*
     * curlHeadersAsArray parses the curl headers into an array
     *
     * http://stackoverflow.com/questions/10589889/returning-header-as-array-using-curl
     * Original reply on stack overflow by Markus Knappen Johansson http://stackoverflow.com/users/872432/markus-knappen-johansson
     *
     * @param $headerContent
     * @return array
     */
    private function _curlHeadersAsArray($headerContent)
    {

        $headers = array();

        // Split the string on every "double" new line.
        $arrRequests = explode("\r\n\r\n", $headerContent);

        // Loop of response headers. The "count() -1" is to
        //avoid an empty row for the extra line break before the body of the response.
        for ($index = 0; $index < count($arrRequests) -1; $index++) {

            foreach (explode("\r\n", $arrRequests[$index]) as $i => $line)
            {
                if ($i === 0)
                    $headers[$index]['http_code'] = $line;
                else
                {
                    list ($key, $value) = explode(': ', $line);
                    $headers[$index][$key] = $value;
                }
            }
        }
        return $headers;
    }
    
    private function agzdecode($data) { 
    	/*if (function_exists("gzdecode")) {
    		return gzdecode($data); 
    	}*/
    	return gzinflate(substr($data,10,-8)); 
	}
}


?>