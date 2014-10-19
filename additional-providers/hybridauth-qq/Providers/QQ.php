<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2012, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html 
*/

/**
 * Hybrid_Providers_QQ provider adapter based on OAuth2 protocol
 * 
 * @version		0.5   QQ.php  add by 韦维 2014-09-27 16:48
 * @author		韦维<weivain@qq.com>www.weiva.com
 * @link		http://www.weiva.com
 *
 * 本代码是在 hybridauth 项目中通过 OAuth2 实现腾讯 QQ 登录，方便在其他 php 项目中提高用户体
 * 验。在开发过程中，参考了 hybridauth 项目的其他模块、腾讯官方开发文档、AlloVince 开
 * 发的 EvaOAuth 模块（http://avnpc.com/pages/evaoauth ），其他部分函数 
 * 参考了网上资料。本代码如未做特殊申明，继承 hybridauth 项目版权声明及其他涉及的第三
 * 方版权声明，请在使用过程中，尊重相应的版权。本程序基于 hybridauth 2.1.1-dev 编写
 * 后续版本由于时间关系未来得及测试。
 * 
 * 本人非专业php程序员，有一些算法不一定正确，欢迎指正。此外，由于本人英文水平有限，程序中一
 * 些英文文档直接照搬其他模块的文档，如有不妥之处，还请谅解。
 */
class Hybrid_Providers_QQ extends Hybrid_Provider_Model_OAuth2
{
    // > more infos on Tencent QQ connect: http://connect.qq.com/ (official site)
	// 关于QQ登录的其他信息，请关注腾讯QQ互联

	// default permissions 
	public $openid;
	public $scope = "get_user_info";//其他 aip 请参考官方开发手册

	/**
	* IDp wrappers initializer 
	*/
	function initialize() 
	{
		parent::initialize();

		// Provider api end-points
		$this->api->authorize_url  = "https://graph.qq.com/oauth2.0/authorize";
		$this->api->token_url      = "https://graph.qq.com/oauth2.0/token";
		$this->api->token_info_url = "https://graph.qq.com/user/get_user_info";
        
		// Override the redirect uri when it's set in the config parameters. This way we prevent
		// redirect uri mismatches when authenticating with Tencent.
		if( isset( $this->config['redirect_uri'] ) && ! empty( $this->config['redirect_uri'] ) ){
			$this->api->redirect_uri = $this->config['redirect_uri'];
		}
	}

	  /**
	   * 开始登录步骤
	   */
	  function loginBegin()
	  {
		if (!isset($this->state)) {
		  $this->state = md5(uniqid(rand(), TRUE));
		}
		$session_var_name = 'state_' . $this->api->client_id;
		$_SESSION[$session_var_name] = $this->state;
		$extra_params['state'] = $this->state;
		$extra_params['response_type'] = "code";

		if (isset($this->scope)) {
		  $extra_params['scope'] = $this->scope;
		}

		Hybrid_Auth::redirect($this->api->authorizeUrl($extra_params));
	  }


	  /**
	   * set proper headers before posting
	   * 设置主机头信息
	   */
	  function post($url) {
		$this->api->curl_header =
		  array(
			'Authorization: Bearer ' . $this->api->access_token,
			'Content-Type: application/x-www-form-urlencoded',
			'Accept: application/json',
			);
		$response = $this->api->post($url);
		return $response;
	  }

	/**
	* load the user profile from the IDp api client
	* 取得用户信息
	*/
	function getUserProfile()
	{
		// refresh tokens if needed
		$this->refreshToken();


		// get user profile
		$response = $this->api->api('https://graph.qq.com/user/get_user_info?'. http_build_query(
			array(
					'openid'=>$this->getOpenId(),
					'oauth_consumer_key'=>$this->api->client_id,
					'access_token'=>$this->api->access_token
			)
		));
		//var_dump($response);
		if (!isset($response->nickname)) {
		  throw new Exception( "User profile request failed! {$this->providerId} returned an invalid response.", 6 );
		}

		// match the fields of the returned data with
		// the standard fields of the hybridauth profile
		$this->user->profile->identifier    = $this->getOpenId();
		$this->user->profile->displayName   = (property_exists($response,'nickname'))?$response->nickname:"";
		$this->user->profile->photoURL      = (property_exists($response,'figureurl_qq_1'))?$response->figureurl_qq_1:"";
		//$this->user->profile->email         = (property_exists($response,'mail'))?$response->mail:"";
		//$this->user->profile->emailVerified = (property_exists($response,'mail'))?$response->mail:"";
		//$this->user->profile->language      = (property_exists($response,'language'))?$response->language:"";

		// pass as well all the returned data
		// on an extra field called 'remote_profile'
		$this->user->profile->remote_profile = $response;

		return $this->user->profile;
	}

	  /**
	   * finish login step
	   * 完成登录步骤
	   */
	  function loginFinish()
	  {
		// check that the CSRF state token is the same as the one provided
		$session_var_name = 'state_' . $this->api->client_id;
		if (isset($_SESSION[$session_var_name])) {
		  $state = $_SESSION[$session_var_name];
		}
		if (!isset($state) || !isset($_REQUEST['state'])
		|| $state !== $_REQUEST['state']) {
		  throw new Exception('Authentication failed! CSRF state token does not match the one provided.');
		}
		unset($_SESSION[$session_var_name]);

		// call the parent function
		parent::loginFinish();
	  }

	public function getOpenId()
	{
		if($this->openid || false === $this->openid) {
			return $this->openid;
		}
        $url = 'https://graph.qq.com/oauth2.0/me?access_token='.$this->api->access_token;
        $this->change_callback($this->visit_url($url));
		//var_dump($str);
		return $this->openid;//返回经过json转码后的数组
	}

    /**
     * 请求URL地址，得到返回字符串
     * @param $url qq提供的api接口地址
     * */
    public function visit_url($url){
        static $cache = 0;
        //判断是否之前已经做过验证
        if($cache === 1){
            $str = $this->curl($url);
        }elseif($cache === 2){
            $str = $this->openssl($url);
        }else{
            //是否可以使用cURL
            if(function_exists('curl_init')){
                $str = $this->curl($url);
                $cache = 1;
                //是否可以使用openssl
            }elseif(function_exists('openssl_open') && ini_get("allow_fopen_url")=="1"){
                $str = $this->openssl($url);
                $cache = 2;
            }else{
                throw new Exception('请开启php配置中的php_curl或php_openssl');
            }
        }
        return $str;
    }
    /**
     * 将字符串转换为可以进行json_decode的格式
     * 将转换后的参数值赋值给成员属性$this->client_id,$this->openid
     * @param $str 返回的callback字符串 
     * @return 数组
     * */
    protected function change_callback($str){
        if (strpos($str, "callback") !== false){
            //将字符串修改为可以json解码的格式
            $lpos = strpos($str, "(");
            $rpos = strrpos($str, ")");
            $json  = substr($str, $lpos + 1, $rpos - $lpos -1);
            //转化json
            $result = json_decode($json,true);
            $this->client_id = $result['client_id'];
            $this->openid = $result['openid'];
            return $result;
        }else{
            return false;
        }
    }
	 /**
     * 通过curl取得页面返回值
     * 需要打开配置中的php_curl
     * */
    private function curl($url){
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);//允许请求的内容以文件流的形式返回
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);//禁用https
        curl_setopt($ch,CURLOPT_URL,$url);//设置请求的url地址
        $str = curl_exec($ch);//执行发送
        curl_close($ch);
        return $str;
    }
    /**
     * 通过file_get_contents取得页面返回值
     * 需要打开配置中的allow_fopen_url和php_openssl
     * */
    private function openssl($url){
        $str = file_get_contents($url);//取得页面内容
        return $str;
    }
 
}
