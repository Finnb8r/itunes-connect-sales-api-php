# itunes-connect-sales-api-php
PHP iTunes Connect Sales Reports API

## Features
- Simple, PHP class that returns iTunes Connect Sales Reports in JSON 
- Get daily, weekly, monthly or yearly sales report directly from iTunes Connect

## Requirements ##
* PHP 
* Valid iTunes Connect Account<br><br>
**Watch out, your vendor ID might not be the one shown on "Payments and reports" page !** <br>
Get your vendor id by : <br>
>Login to iTunes Connect<br>
>Go to "Sales and trends"<br>
>Click on "Top content" ("Classement des contenus" in French) and then "Reports"<br>
>You'll see the list of vendors


## Getting Started ##
Simply require the iTunesSalesApi.php and you're good to go <br>
`require_once("class/iTunesSalesApi.php");`
<br>
Create a new instance of iTunesSalesApi<br>
```php
$reporter =  new iTunesSalesApi();

$reporter->setLogin("mylogin@example.com")
			->setPassword("myPassword")
			->setVendor("myVendorId");
```
## Quick Example ##
### Daily report ###
```php
try{
	//$data is either an array or false
    //if false you can get errors by calling getErrorsAsString() or listing $reporter->errors
    //Date must be YYYYMMDD format (ex: 20161122) for the 12th november 2016
	if($data = $reporter->getSalesDailyReport("20161122")) { // You do not need to specify a date (defaut is yesterday)
		//Do something with data your're good to go
		print_r(json_encode($data));
	}
	else{
		echo "Api  Errors : ".$reporter->getErrorsAsString();
	}
}catch (Exception $e){
	echo $e->getMessage();
}
```
### Weekly report ###
```php
try{
	//Date must be YYYYMMDD format (ex: 20161122) for the 12th november 2016 and week will be calculated
	if($data = $reporter->getSalesWeeklyReport()) { //Will get last week by default
		//Do something with data your're good to go
		print_r(json_encode($data));
	}
	else{
		echo "Api  Errors : ".$reporter->getErrorsAsString();
	}
}catch (Exception $e){
	echo $e->getMessage();
}
```
### Monthly report ###
```php
try{
	//Date must be YYYYMM format (ex: 201611) for november 2016
	if($data = $reporter->getSalesMonthlyReport()) { //Will get last month by default
		//Do something with data your're good to go
		print_r(json_encode($data));
	}
	else{
		echo "Api  Errors : ".$reporter->getErrorsAsString();
	}
}catch (Exception $e){
	echo $e->getMessage();
}
```
### Yearly report ###
```php
try{
	//Date must be YYYY format (ex: 2016) 
	if($data = $reporter->getSalesYearlyReport()) { //Will get last year by default
		//Do something with data your're good to go
		print_r(json_encode($data));
	}
	else{
		echo "Api  Errors : ".$reporter->getErrorsAsString();
	}
}catch (Exception $e){
	echo $e->getMessage();
}
```
## Optional settings ##
If you want to save the reports locally, you can specify a folder you wish to save them to : <br>
```php
$reporter->setFolder("/path/to/my/folder"); //Not saved locally by default (no cache either)
```
If you want to stop script when non critical errors are encountered, set throwErrors to true : <br>
```php
$reporter->throwErrors = true; //False by default
```
If you have specified a folder, API will load previous cached files if available. If you do not want to use the cached file but want a fresh set of data, you can force the refresh request : <br>
```php
$reporter->setUseCache(false); //True by default
```
If you are only interested in how much money you're making, you can skip the free items of sale by setting the report mode to earnings only: <br>
```php
$reporter->setReportModeEarningsOnly();  //Default is $reporter->setReportModeAll();
```
## Todo list ##
- Manage other types of reports (Subscription, Subscription Event and Newstand) : settings are there but have no idea of the possible outputs of such requests
- Return iTunes connect raw data if needed
