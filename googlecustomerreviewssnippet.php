<?php
/**
* 2007-2017 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2017 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
	exit;
}

class GoogleCustomerReviewsSnippet extends Module
{
	protected $config_form = false;

	public function __construct()
	{
		$this->name = 'googlecustomerreviewssnippet';
		$this->tab = 'seo';
		$this->version = '0.1.0';
		$this->author = 'Traxlead';
		$this->need_instance = 0;

		/**
		 * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
		 */
		$this->bootstrap = true;

		parent::__construct();

		$this->displayName = $this->l('Google Customer Reviews Snippet');
		$this->description = $this->l('This modules adds the Google Customer Reviews snippet in the order confirmation page.');

		$this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

		$this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
	}

	/**
	 * Don't forget to create update methods if needed:
	 * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
	 */
	public function install()
	{
		Configuration::updateValue('GCRS_ENABLED', false);

		return parent::install() &&
			$this->registerHook('header') &&
			$this->registerHook('backOfficeHeader') &&
			$this->registerHook('displayOrderConfirmation');
	}

	public function uninstall()
	{
		Configuration::deleteByName('GCRS_ENABLED');

		return parent::uninstall();
	}

	/**
	 * Load the configuration form
	 */
	public function getContent()
	{
		/**
		 * If values have been submitted in the form, process.
		 */
		if (((bool)Tools::isSubmit('submitGoogleCustomerReviewsSnippetModule')) == true) {
			$this->postProcess();
		}

		$this->context->smarty->assign('module_dir', $this->_path);

		$output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

		return $output.$this->renderForm();
	}

	/**
	 * Create the form that will be displayed in the configuration of your module.
	 */
	protected function renderForm()
	{
		$helper = new HelperForm();

		$helper->show_toolbar = false;
		$helper->table = $this->table;
		$helper->module = $this;
		$helper->default_form_language = $this->context->language->id;
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

		$helper->identifier = $this->identifier;
		$helper->submit_action = 'submitGoogleCustomerReviewsSnippetModule';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
			.'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');

		$helper->tpl_vars = array(
			'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
			'languages' => $this->context->controller->getLanguages(),
			'id_language' => $this->context->language->id,
		);

		return $helper->generateForm(array($this->getConfigForm()));
	}

	/**
	 * Create the structure of your form.
	 */
	protected function getConfigForm()
	{
		return array(
			'form' => array(
				'legend' => array(
				'title' => $this->l('Settings'),
				'icon' => 'icon-cogs',
				),
				'input' => array(
					array(
						'col' => 8,
						'type' => 'text',
						'prefix' => '<i class="icon icon-barcode"></i>',
						'desc' => $this->l('Enter your Google Merchant ID'),
						'name' => 'GCRS_MERCHANT_ID',
						'label' => $this->l('Merchant ID'),
					),
				),
				'submit' => array(
					'title' => $this->l('Save'),
				),
			),
		);
	}

	/**
	 * Set values for the inputs.
	 */
	protected function getConfigFormValues()
	{
		return array(
			'GCRS_ENABLED' => Configuration::get('GCRS_ENABLED'),
			'GCRS_MERCHANT_ID' => Configuration::get('GCRS_MERCHANT_ID')
		);
	}

	/**
	 * Save form data.
	 */
	protected function postProcess()
	{
		$form_values = $this->getConfigFormValues();

		foreach (array_keys($form_values) as $key) {
			Configuration::updateValue($key, Tools::getValue($key));
		}
	}

	/**
	* Add the CSS & JavaScript files you want to be loaded in the BO.
	*/
	public function hookBackOfficeHeader()
	{
		if (Tools::getValue('module_name') == $this->name) {
			$this->context->controller->addJS($this->_path.'views/js/back.js');
			$this->context->controller->addCSS($this->_path.'views/css/back.css');
		}
	}

	/**
	 * Add the CSS & JavaScript files you want to be added on the FO.
	 */
	public function hookHeader()
	{
		$this->context->controller->addJS($this->_path.'/views/js/front.js');
		$this->context->controller->addCSS($this->_path.'/views/css/front.css');
	}

	public function hookDisplayOrderConfirmation()
	{
		/* Place your code here. */
		// Get Order ID from GET variable
		$orderId = Tools::getValue('id_order');
		// Get the Order object
		$order = new Order($orderId);

		// If order isn't successful
		if ($order->current_state != _PS_OS_PAYMENT_)
			return false;

		// Get the Customer object
		$customer = new Customer($order->id_customer);
		// Get the Address object
		$address = new Address($order->id_address_delivery);
		// Get the Country object
		$country = new Country($address->id_country);
		
		$this->context->smarty->assign(array(
				'merchant_id'             => Configuration::get('GCRS_MERCHANT_ID'),
				'order_id'                => $orderId,
				'customer_email'          => $customer->email,
				'country'                 => $country->iso_code,
				'estimated_delivery_date' => $this->computeDeliveryDate()
			)
		);

		$this->computeDeliveryDate();

		$view = $this->context->smarty->fetch($this->local_path . 'views/templates/front/customer_reviews_snippet.tpl');

		$this->logCustomers($view);

		return $view;
	}


        private function logCustomers($data)
        {
                $now = new DateTime();

                $output = "==========" . $now->format('Y-m-d H:i:s') . "==========\n";
		$output.= $data . "\n";		

		file_put_contents($this->local_path . 'log.txt', $output, FILE_APPEND);
	}

	private function computeDeliveryDate()
	{
		// [1] Monday -> [7] Sunday
		$businessDays = array(1, 2, 3, 4, 5 );

		$max = 100;

		$deliveryTime = 2;

		$dateTime = new DateTime();
		$deliveryDateTime = clone $dateTime;

		while( $deliveryTime )
		{
			$dateTime->add(new DateInterval('P1D'));
			if( in_array( $dateTime->format('N'), $businessDays ) )
				$deliveryTime--;

			$deliveryDateTime->add(new DateInterval('P1D'));
		}

		return $deliveryDateTime->format('Y-m-d');
	}
}
