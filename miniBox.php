<?php
/**
 * 迷你云入口.
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
/**
 * 老版本
 * Class oldVersion
 */
class OldVersion{
    /**
     * 白名单
     * @var array
     */
    private $whiteList;
    /**
     * 黑名单
     * @var
     */
    private $blackList;
    /**
     * 控制器名
     * @var
     */
    private $controllerName;
    /**
     * 动作名
     * @var
     */
    private $actionName;
    /**
     * 是否离线
     * @var
     */
    private $offline;

    public function OldVersion($offline){
        $this->offline = $offline;
        //解析形如/index.php/site/login?backUrl=/index.php/box/index这样的字符串
        //提取出controller与action
        $requestUri   = Util::getRequestUri();
        $uriInfo      = explode("/",$requestUri);
        $this->controllerName = $uriInfo[1];
        $actionInfo   = explode("?",$uriInfo[2]);
        $this->actionName = $actionInfo[0];
        $this->whiteList = array(
            "install",
			"repairDb",
            //插件
            "miniStore",
        );
        $this->blackList = array(
            "site/login"
        );
    }
    public function load($webApp){


        if($this->offline){
            $this->loadContent($webApp);
            exit;
        }else{
            //查询黑名单，让新版本加载
            foreach($this->blackList as $item){
                $info = explode("/",$item);
                $itemController = $info[0];
                $itemAction = $info[1];
                if($itemController === $this->controllerName && $itemAction === $this->actionName){
                    return false;
                }
            }
            foreach($this->whiteList as $item){
                if($item == $this->controllerName){
                    $this->loadContent($webApp);
                    exit;
                }
            }
            return false;
        }

    }
    private function loadContent($webApp){
        $webApp->run();
    }
}

/**
 * 对来自PC Client的URL进行签名验证并初始化Cookie
 * Class ClientUrl
 */
class PCClientUrl{
    public function initCookie(){
        //如果url地址有了access_token与sign参数，则表示是PC Client的URL地址
        if(array_key_exists("access_token",$_REQUEST) && array_key_exists("sign",$_REQUEST)){
            $requestUri   = Util::getRequestUri();
            $accessToken = $_REQUEST["access_token"];
            $clientID = "d6n6Hy8CtSFEVqNh";
            $clientSecret = "e6yvZuKEBZQe9TdA";
            $info = explode("?",$requestUri);
            $oriUrl = "/".$info[0]."?access_token=".$accessToken."&client_id=".$clientID."&client_secret=".$clientSecret;
            //如果签名信息一致，则可初始化Cookie
            if(md5($oriUrl)===$_REQUEST["sign"]){
                setcookie("accessToken", $accessToken,0,"/");
                setcookie("siteId", MiniSiteUtils::getSiteID(),0,"/");
                setcookie("appKey", $clientID,0,"/");
                setcookie("appSecret", $clientSecret,0,"/");
            }

        }
    }
}
class Util{
    /**
     * 获得迷你云Host
     */
    public static function getMiniHost(){
        $serverPort = $_SERVER["SERVER_PORT"];
        $url = "http://";
        if($serverPort==="443"){
            $url = "https://";
        }
        $serverName = $_SERVER["SERVER_NAME"];
        $url .=$serverName;
        if(!($serverPort==="80" || $serverPort==="443")){
            $url .=":".$serverPort;
        }
        //计算相对地址
        $documentRoot  = $_SERVER["DOCUMENT_ROOT"];
        $scriptFileName = $_SERVER["SCRIPT_FILENAME"];
        $relativePath = substr($scriptFileName,strlen($documentRoot),strlen($scriptFileName)-strlen($documentRoot));
        $path = dirname($relativePath);
        //兼容Windows服务器，把右斜杠替换为左边斜杠
        $path = str_replace("\\","/",$path);
        if($path!=="/"){
            $path.="/";
        }
        return $url.$path;
    }

    /**
     * 获得RequestUri,如果是二级目录、三级目录则自动去掉路径前缀
     * @return string
     */
    public static function getRequestUri(){
        $host = Util::getMiniHost();
        $host = str_replace("http://","",$host);
        $host = str_replace("https://","",$host);
        $host = str_replace("//","/",$host);
        $info = explode("/",$host);
        $relativePath = "";
        for($i=1;$i<count($info);$i++){
            $relativePath .= "/".$info[$i];
        }
        $requestUri   = $_SERVER["REQUEST_URI"];
        return substr($requestUri,strlen($relativePath),strlen($requestUri)-strlen($relativePath));

    }
    public static function getPhysicalRoot(){
        $indexFile = $_SERVER["SCRIPT_FILENAME"];
        $serverPath = dirname($indexFile)."/";
        return $serverPath;
    }
    public static function getParam($name){
        switch($_SERVER['REQUEST_METHOD'])
        {
            case 'GET': $request = &$_GET; break;
            case 'POST': $request = &$_POST; break;
        }
        if(array_key_exists($name,$request)){
            return $request[$name];
        }
        return NULL;
    }

    /**
     * 判断是否是IE浏览器
     * @return bool
     */
    public static function isIE(){
        $pos = strpos(strtolower($_SERVER["HTTP_USER_AGENT"]),"trident");
        if($pos){
            return true;
        }
        return false;
    }

    /**
     * 获得IE浏览器版本
     */
    public static function getIEVersion(){

        if(Util::isIE()){
            $agent = strtolower($_SERVER["HTTP_USER_AGENT"]);
            $start = strpos($agent,"trident");
            $end = strpos($agent,";",$start);
            if(!$end){
                $end = strlen($agent)-1;
            }
            $versionStr = substr($agent,$start+8,$end);
            return intval($versionStr)+4;
        }
        return -1;
    }
    /**
     * 判断是否是Chrome浏览器
     * @return bool
     */
    public static function isChrome(){
        $pos = strpos(strtolower($_SERVER["HTTP_USER_AGENT"]),"chrome");
        if($pos){
            return true;
        }
        return false;
    }
    /**
     * 判断是否是IE浏览器
     * @return bool
     */
    public static function isFirefox(){
        $pos = strpos(strtolower($_SERVER["HTTP_USER_AGENT"]),"firefox");
        if($pos){
            return true;
        }
        return false;
    }
    public static function createI18nUrl($host,$mainPath,$appName,$language,$version){
        if(!($language=="zh_tw"||$language=="zh_cn"||$language=="en")){
            $language="zh_cn";
        }
        return $host."statics/".$mainPath."/i18n/".$language."/".$appName.".js?v=".$version;
    }
}
class SiteAppInfo{
    private $user;
    public function  SiteAppInfo(){

    }
    /**
     * 获得站点信息，获得自定义的名称与Logo
     * @return array|null
     */
    public  function getSiteInfo(){
        $app = new AppService();
        return $app->info();
    }
    /**
     * 判断是否是默认账号
     * @return array|null
     */
    public  function defaultAccount(){
        $app = new AppService();
        return $app->onlyDefaultAccount();
    }
    /**
     * 获得当前用户
     * @return array|null
     */
    public  function getUser(){
        if(isset($this->user)){
            return $this->user;
        }
        $user     = MUserManager::getInstance()->getCurrentUser();
        if(!empty($user)){
            $user = MiniUser::getInstance()->getUser($user["id"]);
            $data = array();
            $data['user_uuid']         = $user["user_uuid"];
            $data['user_name']         = $user["user_name"];
            $data['display_name']      = $user["nick"];
            $data['space']             = (double)$user["space"];
            $data['used_space']        = (double)$user["usedSpace"];
            $data['email']             = $user["email"];
            $data['phone']             = $user["phone"];
            $data['avatar']            = $user["avatar"];
            $data['is_admin']          = $user["is_admin"];
            $this->user = $data;
            return $data;
        }
        return NULL;
    }
}
/**
 *
 * 加载资源
 */
class MiniBox{
    /**
     * 控制器名称
     * @var
     */
    private $controller;
    /**
     * 动作名称
     * @var
     */
    private $action;
    /**
     * 迷你云服务器地址
     * @var
     */
    private $staticsServerHost;
    /**
     * 当前用户选择的语言版本
     * @var
     */
    private $language;
    /**
     * 网页客户端版本号
     * @var
     */
    private $version;
    /**
     * 云端存储的主目录
     * @var
     */
    private $cloudFolderName;
    /**
     * 网页客户端是否在本地离线
     * @var
     */
    private $offline;
    /**
     *是否是网页客户端
     * @var
     */
    private $isWeb = true;
    private $appInfo;
    /**
     * 是否是PC客户端
     * @var
     */
    private $isPC = false;

    /**
     *
     */
    private $webApp = NULL;
    public function MiniBox(){
        $requestUri   = Util::getRequestUri();
        //如果系统尚未初始化，则直接跳转到安装页面
        $configPath  = dirname(__FILE__).'/protected/config/miniyun-config.php';
        if (!file_exists($configPath) && !strpos($requestUri,"install")) {
            header('Location: '.Util::getMiniHost()."index.php/install/index");
            exit;
        }
        //加载系统信息
        $config = dirname(__FILE__).'/protected/config/main.php';
        $yii    = dirname(__FILE__).'/yii/framework/yii.php';
        require_once($yii);
        $this->webApp = Yii::createWebApplication($config);
        MiniAppParam::getInstance()->load();
        MiniPlugin::getInstance()->load();

        //对来自PC Client的URL请求进行Cookie初始化
        $clientUrl = new PCClientUrl();
        $clientUrl->initCookie();
        //根据物理路径判断网页客户端本地是否存在
        $this->offline = $this->isOffline();
        //根据外部的参数判断是什么客户端
        $clientType = strtolower(Util::getParam("client_type"));
        if(empty($clientType)){
            $this->isWeb = true;
            $this->isPC = false;
        }elseif($clientType==="pc"){
            $this->isWeb = false;
            $this->isPC = true;
        }
        $port = $_SERVER["SERVER_PORT"];
        if($port=="443"){
            $this->staticsServerHost = "https://".STATICS_SERVER_HOST."/";
        }else{
            $this->staticsServerHost = "http://".STATICS_SERVER_HOST."/";
        }
        //解析形如/index.php/site/login?backUrl=/index.php/box/index这样的字符串
        //提取出controller与action

        $uriInfo      = explode("/",$requestUri);
        if(count($uriInfo)===1){
            //用户输入的是根路径
            $url = Util::getMiniHost()."index.php/box/index";
            $this->redirectUrl($url);
        }
        //兼容/index.php/box，自动到/index.php/box/index下
        $this->controller = $uriInfo[1];
        if($this->controller==="k"){
            //外链短地址
            $key = $uriInfo[2];
            $url = Util::getMiniHost()."index.php/link/access/key/".$key;
            $this->redirectUrl($url);
        }
        if(count($uriInfo)===2||empty($uriInfo[2])){
            $url = Util::getMiniHost()."index.php/".$this->controller."/index";
            $this->redirectUrl($url);
        }else{
            $actionInfo   = explode("?",$uriInfo[2]);
            $this->action = $actionInfo[0];
        }
        if(empty($this->controller)){
            $accessToken = $this->getCookie("accessToken");
            if(!empty($accessToken)){
                //根目录访问
                if($this->offline){
                    $url = Util::getMiniHost()."index.php/netdisk/index";
                }else{
                    $url = Util::getMiniHost()."index.php/box/index";
                }
            }else{
                $url = Util::getMiniHost()."index.php/site/login";
            }
            $this->redirectUrl($url);
        }
    }

    /**
     * 调整URL，如是PC客户端发起的请求，在后面自动加上client_type=pc类型
     * 区分是否是PC客户端还是网页客户端发起的请求
     * @param $url
     */
    private function redirectUrl($url){
        //如果是PC客户端请求，把后面加上client_type=pc
        if($this->isPC){
            if(strpos($url,"?")){
                $url .= "&client_type=pc";
            }
            $url .= "?client_type=pc";
        }
        header('Location: '.$url);
        exit;
    }
    private  function loadHtml($head){
        $ieHead = "";
        if(Util::isIE()){
            $ieHead = "<meta http-equiv='X-UA-Compatible' content='IE=edge,chrome=1'/>";
        }
        //输出头信息
        $content = "<!doctype html><html id='ng-app'><head><meta http-equiv=\"content-type\" content=\"text/html;charset=utf-8\"/>".$ieHead.$head."<script>";
        $appInfo = $this->appInfo;
        //打印APP INFO
        $content .= "var appInfo={};appInfo.info = JSON.parse('".json_encode($appInfo->getSiteInfo())."');";
        //是否系统仅有管理员
        $content .= "appInfo.only_default_account = JSON.parse('".json_encode($appInfo->defaultAccount())."');";
        //打印用户是否登录
        $user    = $appInfo->getUser();
        //迷你存储或第3方存储系统
        $thirdStoreInfo = MiniHttp::getThirdStoreInfo();
        $info = array("success"=>true);
        if(empty($user)){
            $info = array("success"=>false);
        }
        $content .= "appInfo.third_store = JSON.parse('".json_encode($thirdStoreInfo)."');";
        $content .= "appInfo.login = JSON.parse('".json_encode($info)."');";
        if(!empty($user)){
            $content .= "appInfo.user = JSON.parse('".json_encode($user)."');";
        }
        //打印用户是否是管理员
        $info = array("success"=>false);
        if(!empty($user)){
            if($user["is_admin"]){
                $info = array("success"=>true);
            }
        }
        $content .= "appInfo.is_admin = JSON.parse('".json_encode($info)."');";
        //输出服务器时间
        $info = array("time"=>time());
        $content .= "appInfo.time = JSON.parse('".json_encode($info)."');";
        //输出body信息
        $content .= "</script></head><body><div ng-view></div></body></html>";
        echo($content);
    }
    /**
     * 加载资源
     */
    public function load(){
        date_default_timezone_set("PRC");
        @ini_set('display_errors', '1');
        if($this->isWeb){
            //兼容老版本逻辑
            $oldVersion = new OldVersion($this->offline);
            $oldVersion->load($this->webApp);
        }

        $this->appInfo = new SiteAppInfo();

        $this->syncNewVersion();
        //默认业务主路径
        $this->cloudFolderName = "mini-box";

        $language = $this->getCookie("language");
        if(empty($language)){
            $language = "zh_cn";
        }
        $this->language = $language;
        $v = $this->getCookie("cloudVersion");
        if(empty($v)){
            $v = "1.0";
        }
        if(YII_DEBUG){
            $v = time();
        }
        $this->version = $v;
        $header = "";
		$user = $this->appInfo->getUser();
		if(isset($user)){
			$siteInfo = "s=".MiniSiteUtils::getSiteID();
			$siteInfo .= "&u=".$user["user_uuid"]; 
		}
		//生产状态，将会把js/css文件进行合并处理，提高加载效率
		$header .= "<script id='miniBox' statics-server-host='".$this->staticsServerHost."' host='".Util::getMiniHost()."' version='".$v."' type=\"text/javascript\"  src='".$this->staticsServerHost."miniLoad.php?t=js&c=".$this->controller."&a=".$this->action."&v=".$v."&l=".$this->language."' charset=\"utf-8\"></script>";
		$header .= "<link rel=\"stylesheet\" type=\"text/css\"  href='".$this->staticsServerHost."miniLoad.php?t=css&c=".$this->controller."&a=".$this->action."&v=".$v."&l=".$this->language."'/>"; 
        $this->loadHtml($header);
    }
     

    /**
     * 判断网页客户端是否离线
     * @return bool
     */
    private function isOffline(){
        $syncTime = $this->getCookie("syncTime");
        if(!empty($syncTime) && $syncTime==="-1"){
            return true;
        }
        return false;
    }
    /**
     *每隔24小时与云端同步一次版本信息，用户在不清理缓存的情况下，24小时更新到最新版本迷你云网页版
     */
    private function syncNewVersion(){
        //如本地尚未安装网页客户端，则跳转到online.html，通过online.html的js请求获得相关数据
        $needSyncCloud = false;
        //24小时后至少与云端同步一次最新网页客户端代码
        $syncTime = $this->getCookie("syncTime");
        if($syncTime===NULL){
            $diff = time()-intval($syncTime);
            if($diff>86400){
                $needSyncCloud = true;
            }
        }else if($syncTime===""){
            $needSyncCloud = true;
        }
        if($needSyncCloud===true){
            $url = Util::getMiniHost()."online.html?t=".time()."&back=".urlencode($_SERVER["REQUEST_URI"])."&staticsServerHost=".$this->staticsServerHost;
            $this->redirectUrl($url);
        }
    }
    /**
     *
     * 根据浏览器的版本返回语言
     */
    private function getCookie($name){
        $value = null;
        if(array_key_exists($name,$_COOKIE)){
            $value = $_COOKIE[$name];
        }
        return $value;
    }
}