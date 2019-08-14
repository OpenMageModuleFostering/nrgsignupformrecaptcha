<?php

require_once(Mage::getBaseDir('app') . DS .'code'. DS .'core'. DS .'Mage'. DS .'Customer'. DS .'controllers'. DS .'AccountController.php');

class Tri_SignupFormCaptcha_AccountController extends Mage_Customer_AccountController {
    const XML_PATH_CFC_ENABLED     = 'tri_options/general/enabled';
    const XML_PATH_CFC_PUBLIC_KEY  = 'tri_options/general/public_key';
    const XML_PATH_CFC_PRIVATE_KEY = 'tri_options/general/private_key';
    const XML_PATH_CFC_THEME       = 'tri_options/general/theme';
    const XML_PATH_CFC_LANG        = 'tri_options/general/lang';


    
    public function preDispatch() {
        parent::preDispatch();
    }
     public function createAction() {
	        $this->loadLayout();
        $this->getLayout()->getBlock('customer_form_register')->setFormAction(Mage::getUrl('*/ */post'));

        if (Mage::getStoreConfigFlag(self::XML_PATH_CFC_ENABLED)) {
         
            require_once(Mage::getBaseDir('lib') . DS .'reCaptcha'. DS .'recaptchalib.php');
            
           
            $publickey = Mage::getStoreConfig(self::XML_PATH_CFC_PUBLIC_KEY);
            $captcha_code = recaptcha_get_html($publickey);
           
            $theme = Mage::getStoreConfig(self::XML_PATH_CFC_THEME);
            if (strlen($theme) == 0 || !in_array($theme, array('red', 'white', 'blackglass', 'clean'))) {
                $theme = 'red';
            }

            
            $lang = Mage::getStoreConfig(self::XML_PATH_CFC_LANG);
            if (strlen($lang) == 0 || !in_array($lang, array('en', 'nl', 'fr', 'de', 'pt', 'ru', 'es', 'tr'))) {
                $lang = 'en';
            }
            
            $captcha_code = str_replace('?k=', '?hl='. $lang .'&amp;k=', $captcha_code);

            $this->getLayout()->getBlock('customer_form_register')->setCaptchaCode($captcha_code)
                                                        ->setCaptchaTheme($theme)
                                                        ->setCaptchaLang($lang);
        }

        $this->_initLayoutMessages('customer/session');
        $this->_initLayoutMessages('catalog/session');
        $this->renderLayout();
    }
    public function createPostAction() {
	
        if (Mage::getStoreConfigFlag(self::XML_PATH_CFC_ENABLED)) {
		
            try {
			
                $post = $this->getRequest()->getPost();
                $formData = new Varien_Object();
                $formData->setData($post);
                Mage::getSingleton('core/session')->setData('customer_form_register', $formData);

                if ($post) {
                    
                    require_once(Mage::getBaseDir('lib') . DS .'reCaptcha'. DS .'recaptchalib.php');
                    
                    
                    $privatekey = Mage::getStoreConfig(self::XML_PATH_CFC_PRIVATE_KEY);
                    $remote_addr = $this->getRequest()->getServer('REMOTE_ADDR');
                    $captcha = recaptcha_check_answer($privatekey, $remote_addr, $post["recaptcha_challenge_field"], $post["recaptcha_response_field"]);

                    if (!$captcha->is_valid) {
                        throw new Exception($this->__("The reCAPTCHA wasn't entered correctly. Go back and try it again."), 1);
                    }

                    Mage::getSingleton('core/session')->unsetData('customer_form_register');
                }
                else {
                    throw new Exception('', 1);
                }
            }
            catch (Exception $e) {
                if (strlen($e->getMessage()) > 0) {
                    Mage::getSingleton('customer/session')->addError($this->__($e->getMessage()));
                }
               $this->_redirect('*/*/create');
                return;
            }
        }

        
        parent::createPostAction();
    }
} 