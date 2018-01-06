<?php
class topm_couponqrc{
	private $rootpath;
	public $qrc_dir;
	function __construct(){
		//默认保存地址
		$this->rootpath = __DIR__ . "/../../../public/";
		$this-> qrc_dir= "upload/qrcpng/"; 
	}
	/**
	 * 默认加载公共类，
	 */
	public function loadingqrcLib(){
		include("phpqrcode.php");
	}
	/**
	 * 设置图片保存的地址，
	 * 注意 前缀已经加好是upload/,中间位置默认为 (qrcpng) ,最后不要再 “/” ，否则出错
	 * 例如： 参数＝qrcpng/abc  ，则最后的地址：upload/qrcpng/abc/xxxxx.png
	 */
	public function setrootDir($rootdir = "qrcpng"){
		if(!$rootdir || trim($rootdir)=="" )$rootdir = "qrcpng";
		$this-> qrc_dir= "upload/{$rootdir}/"; 
		//目录不存在，预先创建
		if(!file_exists($this->rootpath .$this-> qrc_dir )){
			@mkdir( $this->rootpath .$this-> qrc_dir);
		}
	}
	/**
	 * 判断当前内容是否有二维码图片存在
	 * 有：true ; 没有就:false;
	 */
	public function mobilQrcExists( $mobile , & $filepath=""){
		$filepath = $this-> qrc_dir.$mobile.".png";
		if(file_exists($this->rootpath .$filepath)){
			return true;
		}
		return false;
	}
	
    //生成券码
    public function createQrc($targetContent , &$fileName = "" , $isloadingg = true )
    {
    	if($isloadingg) $this->loadingqrcLib(); //加载
    	$fName = $targetContent ; //默认文件名
    	if($targetContent && is_array($targetContent) && count($targetContent) > 1){
    		$fName = !empty($targetContent[1])  ? trim($targetContent[1]) : $targetContent;
    		$targetContent = reset($targetContent);
    	} 
    	$len = strlen($targetContent);
	    if ($len <= 360){
	    	/*$file = fopen("t.txt","r+");
	    	flock($file,LOCK_EX);
	      	if($file) {
		       $get_file = fgetss($file);
		       $t = $get_file+1;
		       $file2 = fopen("t.txt","w+");
		       fwrite($file2,$t);	
	      	}
		    flock($file,LOCK_UN);
		    fclose($file);
		    fclose($file2);*/
		    $fileName = $this-> qrc_dir .$fName.".png";
		    /* 第二个参数$outfile默认为否，不生成文件，只将二维码图片返回，否则需要给出存放生成二维码图片的路径
		     * 第三个参数$level默认为L，这个参数可传递的值分别是L(QR_ECLEVEL_L，7%)，M(QR_ECLEVEL_M，15%)，Q(QR_ECLEVEL_Q，25%)，H(QR_ECLEVEL_H，30%)。这个参数控制二维码容错率
		     * 第四个参数$size，控制生成图片的大小，默认为4
		     * 第五个参数$margin，控制生成二维码的空白区域大小
		     * 第六个参数$saveandprint，保存二维码图片并显示出来，$outfile必须传递图片路径
		     * */
		    $matrixPointSize = "5"; //设置大小 
	   		QRcode::png($targetContent, $this->rootpath.$fileName,"L",$matrixPointSize,2);	
	   		return true;
	   } else {
	     	throw new \LogicException(app::get('sysuser')->_('内容过大')); 
	     	return false;
	   }
	   return false;
    }
}
