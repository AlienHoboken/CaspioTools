CaspioTools
===========

CaspioTools is a PHP toolchain for the Caspio Web Services API that is meant to aid in application development speed and robustness when using the Caspio platform.

CaspioTools effectively abstracts away the usage of the Caspio Web Servies API so that you can spend more time developing your application and less time learning a new API system.

Further, the CaspioTools toolchain provides additional, useful capabilities such as user logins.


Using
-----
1. Setup a new Web Services Profile (WSP)
2. Include CaspioTools.php
3. Instantiate a new CaspioTools object by passing in your Account ID, WSP Name, and WSP Password

```php
require_once('/path/to/CaspioTools.php');
$AccountID = "Your Caspio Username";
$ProfileName = "WSP Name";
$Password = "WSP Password";
$caspio = new CaspioTools($AccountID, $ProfileName, $Password);
```

Now you can use any of the packaged methods/functionalities of CaspioTools explained below!
