<?php

// TODO:
// - Refactor, but keeping original behaviour first
// - Use bootstrap.css
// - Use simple xView (create a git project from RestQL xView implementation)
//   - one view per 'res' state
//   - one window layout, one popup layout
//   - separate js files
// - Keep debugging feature (keep it kiss)
//   - one view for debug
// - Find a way of finding the popup back, in case of accidental close (or provide a cofirm-close dialog)
//   - Generate URL? Keep params in session?
//   - Do it as an optional 'module'
// - Replace $userpassword param with $auth_type (PAP, plain?, other-possibles?)
//   - Discover and make out  page<>UAM HTTP Messages (cf. attempt_login())
// - Heavily cross-browser testable (ie3..chrome9 if possible)
// - Place popup so that it doesn't get overlayed by the main window
// - Create a JS UAM API for querying the NAS
//   - Generates https requests to NAS (automagically using the URL get params)
//   - Catches the 304 Redirect response and parses it's URL GET params)
//   - Creates and returns a hashmap from them
//   => Enables a one-page js-based login app

// Maqui-wifi TODO:
// - Display a map with spots on login/signup/about/pricing page(s)
// - Make a trial account generation system (2 minutes timeout)
//   - "Please enter your email address and receive a free trial login code"
// - 



# UAM Configuration
$uamsecret = "wasa";
# Uncomment the following line if you want to use ordinary user-password
# for radius authentication. Must be used together with $uamsecret.
#$userpassword=1;

## Controller

# 0: Login attempt (if all mandatory authentication parameters are set)
$login_mandatory_params = array('chal', 'uamip', 'uamport', 'username', 'password');
$login_params = array_intersect(array_keys($_GET), $login_mandatory_params);
if (!array_diff($login_mandatory_params, $login_params)) attempt_login();
# 1: Not logged in yet
if ($_GET['res'] == 'notyet') display_notyet();
# 2: Login failed
if ($_GET['res'] == 'failed') display_failed();
# 1: Login successful
if ($_GET['res'] == 'success') display_success();
# 3: Logged out (TODO: Display a timeout message, and options)
if ($_GET['res'] == 'logoff') display_logoff();
if ($_GET['res'] == 'timeout') display_logoff(); // timeout 'res' is not native
# 4: Tried to login while already logged in
if ($_GET['res'] == 'already') display_already();
#12: Success popup (this 'res' is not native)
if ($_GET['res'] == 'success_popup') display_success_popup();

function attempt_login() {
    global $uamsecret, $userpassword;
    
    echo "<h1>Logging in...</h1>";

    $hexchal = pack ("H32", $_GET['chal']);
    $newchal = $uamsecret ? pack("H*", md5($hexchal . $uamsecret)) : $hexchal;

    $response = md5("\0" . $_GET['password'] . $newchal);
    
    $newpwd = pack("a32", $_GET['password']);
    $pappassword = implode ('', unpack("H32", ($newpwd ^ $newchal)));
    
    if ((isset ($uamsecret)) && isset($userpassword)) {
        print implode('', array(
            '<meta http-equiv="refresh" content="0;url=',
            'http://', $_GET['uamip'], ':', $_GET['uamport'], '/',
            'logon?username=', $_GET['username'], '&password=', $pappassword, '">'
        ));
    } else {
        print implode('', array(
            '<meta http-equiv="refresh" content="0;url=',
            'http://', $_GET['uamip'], ':', $_GET['uamport'], '/',
            'logon?username=', $_GET['username'], '&response=', $response,
            '&userurl=', $_GET['userurl'], '">'
        ));
    }
}

function display_notyet() {
// TODO: remove this unused $result (for cleaning step before refactoring)
//    global $result;
//    $result = 5;    

    echo '<h1>Please login</h1>';
    print implode('', array(
        '<style>th {test-align:right}</style>',
        '<form name="form1" method="get" action="">',
            '<input type="hidden" name="chal" value="', $_GET['challenge'], '">',
            '<input type="hidden" name="uamip" value="', $_GET['uamip'], '">',
            '<input type="hidden" name="uamport" value="', $_GET['uamport'], '">',
            '<input type="hidden" name="userurl" value="', $_GET['userurl'], '">',
            '<table>',
                '<tr>',
                    '<th>Login</th>',
                    '<td><input type="text" name="username"></td>',
                '</tr>',
                '<tr>',
                    '<th>Password</th>',
                    '<td><input type="password" name="password"></td>',
                '</tr>',
            '</table>',
            '<input type="submit" value="Login">',
        '</form>'
    ));
}

function display_failed() {
// TODO: remove this unused $result (for cleaning step before refactoring)
//    global $result;
//    $result = 2;

    // TODO: Simply echo message + login form again
    echo '<h1>Login failed :(</h1>';
    print implode('', array(
        '<a href="http://', $_GET['uamip'], ':', $_GET['uamport'], '/prelogin', "?userurl={$_GET['userurl']}", '">',
            'Please try again',
        '</a>'
    ));
}

function display_success() {
    global $result;   
    $result = 1;
    //
    global $title, $headline, $bodytext;
    $loginpath = $_SERVER['PHP_SELF'];
    $title = 'Logged in (display_success)';
    $headline = 'Logged in (display_success)';
    $bodytext =  'You should use this page to log out when you are finished.';
    $bodytext .= '<h2><a href="http://' . $_GET['uamip'] . ':' . $_GET['uamport'] . '/logoff">Logout</a></h2>';
    print_header();
    print_body();
}

function display_success_popup() {
    global $result;
    $result = 12;   
    // TODO: Merge this with 'display_success' logic
    global $title, $headline, $bodytext;
    $title = 'Logged in (display_success_popup)';
    $headline = 'Logged in (display_success_popup)';
    $bodytext =  'You should use this page to log out when you are finished.';
    $bodytext .= '<h2><a href="http://' . $_GET['uamip'] . ':' . $_GET['uamport'] . '/logoff">Logout</a></h2>';
    print_header();
    print_body();
}

function display_logoff() {
// TODO: remove this unused $result (for cleaning step before refactoring)
//    global $result;
//    $result = 3; 
    echo '<h1>Logged out</h1>';
    print implode('', array(
        '<a href="http://', $_GET['uamip'], ':', $_GET['uamport'], '/prelogin', '">',
        '    Login',
        '</a>'
    ));
}

function display_already() {
// TODO: remove this unused $result (for cleaning step before refactoring)
//    global $result;
//    $result = 4;
    echo '<h1>Already logged in</h1>';
    print implode('', array(
        '<a href="http://', $_GET['uamip'], ':', $_GET['uamport'], '/logoff', '">',
        '    Login',
        '</a>'
    ));
}


# HTML rendering functions (kind of)
function print_header() {
    // TODO:
    // Removed features to re-enable:
    // - Username field autofocus.
    // - Popup window focus management (dunno what it did).
    // NOT removed feature, to ENSURE KEEPING:
    // - If popup opening fails somehow, the main page is not redirected to $userurl.
    global $title;
    
    $loginpath = $_SERVER['PHP_SELF'];
    $uamip = $_GET['uamip'];
    $uamport = $_GET['uamport'];
    
    print "
        <html>
        <head>
            <title>$title</title>
            <meta http-equiv=\"Cache-control\" content=\"no-cache\">
            <meta http-equiv=\"Pragma\" content=\"no-cache\">
            <meta http-equiv=\"Content-Type\" content=\"text/html; charset=ISO-8859-1\">
            <SCRIPT LANGUAGE=\"JavaScript\">
            var starttime = new Date();
            var startclock = starttime.getTime();
            var mytimeleft = 0;
            
            function doTime() {
                // TODO: Use Date object operation to compute time differences
                window.setTimeout( \"doTime()\", 1000 );
                t = new Date();
                time = Math.round((t.getTime() - starttime.getTime())/1000);
                if (mytimeleft) {
                    time = mytimeleft - time;
                    // time expired
                    if (time <= 0) {
                         window.location = \"$loginpath?res=timeout&uamip=$uamip&uamport=$uamport\";
                    }
                }
                if (time < 0) time = 0;
                hours = (time - (time % 3600)) / 3600;
                time = time - (hours * 3600);
                mins = (time - (time % 60)) / 60;
                secs = time - (mins * 60);
                if (hours < 10) hours = \"0\" + hours;
                if (mins < 10) mins = \"0\" + mins;
                if (secs < 10) secs = \"0\" + secs;
                title = \"Online time: \" + hours + \":\" + mins + \":\" + secs;
                if (mytimeleft) {
                    title = \"Remaining time: \" + hours + \":\" + mins + \":\" + secs;
                }
                if (document.all || document.getElementById) {
                    document.title = title;
                } else {
                    self.status = title;
                }
            }
        
            function popUp(URL) {
                if (self.name != \"chillispot_popup\") {
                    chillispot_popup = window.open(URL, 'chillispot_popup', 'toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=1,width=500,height=600');
                }
            }
            
            function doOnLoad(result, URL, userurl, timeleft) {
                if (timeleft) {
                    mytimeleft = timeleft;
                }
                if (result == 1) {
                    if (self.name == \"chillispot_popup\") doTime();
                    else chillispot_popup = window.open(URL, 'chillispot_popup', 'toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=1,width=500,height=600');
                }
                if ((result == 12) && (self.name == \"chillispot_popup\")) {
                    doTime();
                    if (userurl) opener.location = userurl;
                    else if (opener.home) opener.home();
                    else opener.location = \"http://www.google.com\";
                }
            }
            </script>
        </head>
    ";
}

function print_body() {
    global $headline, $bodytext, $result;

    $loginpath = $_SERVER['PHP_SELF'];    
    $uamip = $_GET['uamip'];
    $uamport = $_GET['uamport'];
    $userurl = $_GET['userurl'];
    $timeleft = $_GET['timeleft'];
    
    print "
        <body onLoad=\"javascript:doOnLoad($result, '$loginpath?res=success_popup&uamip=$uamip&uamport=$uamport&userurl=$userurl&timeleft=$timeleft','$userurl', '$timeleft')\">
        <h1 style=\"text-align: center;\">$headline</h1>
        <center>$bodytext</center><br>
    ";
}

# begin debugging
print '<hr/><h3>Debug:</h3>';
print '<style>th {text-align:left}</style>';
print '<table>';
foreach ($_GET as $key => $value) print implode('', array(
    '<tr>',
    "    <th>{$key}</th>",
    "    <td>{$value}</td>",
    '</tr>'
));
print '</table>';
# end debugging

exit(0);

?>