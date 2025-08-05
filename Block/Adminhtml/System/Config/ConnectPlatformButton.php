<?php
/**
 * Payrexx Payment Gateway
 *
 * @category  Payrexx
 * @package   Payrexx_PaymentGateway
 * @author    Support <support@payrexx.com>
 * @copyright PAYREXX AG
 */
namespace Payrexx\PaymentGateway\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Renders a button with JavaScript to populate the API key field.
 */
class ConnectPlatformButton extends Field
{
	/**
	 * @var string
	 */
	protected $_template = 'Payrexx_PaymentGateway::system/config/connect_platform_button.phtml';

	/**
	 * @param Context $context
	 * @param array $data
	 */
	public function __construct(Context $context, array $data = [])
	{
		parent::__construct($context, $data);
	}

	/**
	 * Remove scope and label to render just the button.
	 *
	 * @param  AbstractElement $element
	 * @return string
	 */
	public function render(AbstractElement $element)
	{
		$element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
		return parent::render($element);
	}

	/**
	 * Return the block's HTML.
	 *
	 * @param  AbstractElement $element
	 * @return string
	 */
	protected function _getElementHtml(AbstractElement $element)
	{
		return $this->_toHtml();
	}
}
