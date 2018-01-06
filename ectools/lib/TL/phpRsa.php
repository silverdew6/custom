<?php

class ectools_TL_phpRsa{

 function __construct(){
 define("BCCOMP_LARGER", 1);
 }

function rsa_encrypt($message, $public_key, $modulus, $keylength)   
{   
    $padded = $this->add_PKCS1_padding($message, true, $keylength / 8);   
    $number = $this->binary_to_number($padded);   
    $encrypted = $this->pow_mod($number, $public_key, $modulus);   
    $result = $this->number_to_binary($encrypted, $keylength / 8);   
    return $result;   
  
}   


function rsa_decrypt($message, $private_key, $modulus, $keylength)   
{   
    $number = $this->binary_to_number($message);   
    $decrypted = $this->pow_mod($number, $private_key, $modulus);   
    $result = $this->number_to_binary($decrypted, $keylength / 8);   
    return $this->remove_PKCS1_padding($result, $keylength / 8);   
}   


function rsa_sign($message, $private_key, $modulus, $keylength,$hash_func)   
{   
	//only suport sha1 or md5 digest now
	if (!function_exists($hash_func) && (strcmp($hash_func ,'sha1') == 0 || strcmp($hash_func,'md5') == 0))
		return false;
	$mssage_digest_info_hex = $hash_func($message);
	$mssage_digest_info_bin = $this->hexbin($mssage_digest_info_hex);
    $padded = $this->add_PKCS1_padding($mssage_digest_info_bin, false, $keylength / 8);
    $number = $this->binary_to_number($padded);   
    $signed = $this->pow_mod($number, $private_key, $modulus);   
    $result = base64_encode($signed); 
    return $result;   
}   


function rsa_verify($document, $signature, $public_key, $modulus, $keylength,$hash_func)   
{   
	//only suport sha1 or md5 digest now
	if (!function_exists($hash_func) && (strcmp($hash_func ,'sha1') == 0 || strcmp($hash_func,'md5') == 0))
		return false;
	$document_digest_info = $hash_func($document);
	
	$number    = $this->binary_to_number(base64_decode($signature));   
    $decrypted = $this->pow_mod($number, $public_key, $modulus);   
    $decrypted_bytes    = $this->number_to_binary($decrypted, $keylength / 8);   
    if($hash_func == "sha1" )
    {
    	$result = $this->remove_PKCS1_padding_sha1($decrypted_bytes, $keylength / 8);
    }
    else
    {
    	$result = $this->remove_PKCS1_padding_md5($decrypted_bytes, $keylength / 8);
    }
	return($this->hexbin($document_digest_info) == $result);
}   
  
 

protected function pow_mod($p, $q, $r)   
{   
    // Extract powers of 2 from $q   
    $factors = array();   
    $div = $q;   
    $power_of_two = 0;   
    while(bccomp($div, "0") == BCCOMP_LARGER)   
    {   
        $rem = bcmod($div, 2);   
        $div = bcdiv($div, 2);   
  
        if($rem) array_push($factors, $power_of_two);   
        $power_of_two++;   
    }   

    $partial_results = array();   
    $part_res = $p;   
    $idx = 0;   
    
    foreach($factors as $factor)   
    {   
        while($idx < $factor)   
        {   
            $part_res = bcpow($part_res, "2");   
            $part_res = bcmod($part_res, $r);   
            $idx++;   
        }   
        array_push($partial_results, $part_res);   
    }   

    // Calculate final result   
    $result = "1";   
    foreach($partial_results as $part_res)   
    {   
        $result = bcmul($result, $part_res);   
        $result = bcmod($result, $r);   
    }   
    return $result;   
}   

protected function add_PKCS1_padding($data, $isPublicKey, $blocksize)   
{   
    $pad_length = $blocksize - 3 - strlen($data);   
    if($isPublicKey)   
    {   
        $block_type = "\x02";   
        $padding = "";   
        for($i = 0; $i < $pad_length; $i++)   
        {   
            $rnd = mt_rand(1, 255);   
            $padding .= chr($rnd);   
        }   
    }   
    else  
    {   
        $block_type = "\x01";   
        $padding = str_repeat("\xFF", $pad_length);   
    }   
  
    return "\x00" . $block_type . $padding . "\x00" . $data;   
}  
 
  
protected function remove_PKCS1_padding($data, $blocksize)   
{   
    $data = substr($data, 1);   
 
    if($data{0} == '\0')   
        die("Block type 0 not implemented.");   
  
    $offset = strpos($data, "\0", 1);   
    return substr($data, $offset + 1);   
}   

protected function remove_PKCS1_padding_sha1($data, $blocksize) {
	$digestinfo = $this->remove_PKCS1_padding($data, $blocksize);
	$digestinfo_length = strlen($digestinfo);

	return substr($digestinfo, $digestinfo_length-20);
}   

protected function remove_PKCS1_padding_md5($data, $blocksize) {
	$digestinfo = $this->remove_PKCS1_padding($data, $blocksize);
	$digestinfo_length = strlen($digestinfo);
	//md5 digestinfo length not less than 16
	//assert($digestinfo_length >= 16);

	return substr($digestinfo, $digestinfo_length-16);
}
  
protected function binary_to_number($data)   
{   
    $base = "256";   
    $radix = "1";   
    $result = "0";   
  
    for($i = strlen($data) - 1; $i >= 0; $i--)   
    {   
        $digit = ord($data{$i});   
        $part_res = bcmul($digit, $radix);   
        $result = bcadd($result, $part_res);   
        $radix = bcmul($radix, $base);   
    }   
    return $result;   
}     
protected function number_to_binary($number, $blocksize)   
{   
    $base = "256";   
    $result = "";   
    $div = $number;   
    while($div > 0)   
    {   
        $mod = bcmod($div, $base);   
        $div = bcdiv($div, $base);   
        $result = chr($mod) . $result;   
    }   
    return str_pad($result, $blocksize, "\x00", STR_PAD_LEFT);   
}  
protected function hexbin($data) {
  $len = strlen($data);
  $newdata='';
  for($i=0;$i<$len;$i+=2) {
      $newdata .= pack("C",hexdec(substr($data,$i,2)));
  }
  return $newdata;
}
protected function getKey($file, $passphrase, $keyType){
	$p12_File_Name = ($file);
	$certs = array();
	$pkcs12 = file_get_contents($p12_File_Name);
	
	openssl_pkcs12_read($pkcs12, $certs, $passphrase);
	
	#var_dump($certs);//可通过var_dump函数查看输出数组的key
	
	$pubKey = $certs['cert'];//公钥数据
	$priKey = $certs['pkey'];//私钥数据
	
	if($keyType == 'pubKey'){
		return $pubKey;
	}
	if($keyType == 'priKey'){
		return $priKey;
	}
}

function signByPriKey($resource, $file, $passphrase){
	
	$priKey = $this->getKey($file, $passphrase, 'priKey');
	$res = openssl_pkey_get_private($priKey);
	if(openssl_sign($resource, $out, $res)){
		//var_dump(base64_encode($out));
		return base64_encode($out);
	}else{
		return "";
	}
}

function verifyByPubKey($resource, $sign, $file, $passphrase){
	$pubKey = $this->getKey($file, $passphrase, 'pubKey');
	
	$out = base64_decode($sign);
	$res = openssl_pkey_get_public($pubKey);
	
	if(openssl_verify($resource, $out, $res) == 1)
		return 1; //echo "verify_success";		
	else 
		return 0; //echo "verify_failed";
}
 
}