<?php
/**
 * Copyright (c) VCT. All rights reserved
 */
declare(strict_types=1);

namespace Vct\Main\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Module\FullModuleList;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Helper\Js;

/**
 * @copyright Copyright (c) VCT
 * @link https://vct-vendor.github.io
 */
class Modules extends Fieldset
{
    private const VCT_MODULES = [
        'Vct_ChangeSkuDynamically',
        'Vct_SimpleProductUrl',
        'Vct_ProductInfoSwitcher',
        'Vct_AlsoBought',
        'Vct_PriceDiff',
        'Vct_PlaceOrderSidebar',
    ];
    /* phpcs:ignore Generic.Files.LineLength.TooLong */
    private const LOGO_URL_FORMAT = "https://raw.githubusercontent.com/vct-vendor/vct-vendor.github.io/master/static/img/docs/%s.svg";
    private const MODULE_URL_FORMAT = "https://marketplace.magento.com/%s.html#maincontent";

    /**
     * @var FullModuleList
     */
    private FullModuleList $fullModuleList;

    /**
     * @param Context $context
     * @param Session $authSession
     * @param Js $jsHelper
     * @param FullModuleList $fullModuleList
     */
    public function __construct(Context $context, Session $authSession, Js $jsHelper, FullModuleList $fullModuleList)
    {
        parent::__construct($context, $authSession, $jsHelper);
        $this->fullModuleList = $fullModuleList;
    }

    /**
     * Add template to header comment
     *
     * @param AbstractElement $element
     * @return string
     * @throws LocalizedException
     * @SuppressWarnings(CamelCaseMethodName)
     */
    protected function _getHeaderCommentHtml($element): string
    {
        if ($element->getComment()) {
            return parent::_getHeaderCommentHtml($element);
        }

        $html = $this->getLayout()
            ->createBlock(Template::class)
            ->setData('logo_attributes', $this->getLogoAttributes())
            ->setTemplate('Vct_Main::modules.phtml')
            ->toHtml();

        return sprintf('%s%s', $html, parent::_getHeaderCommentHtml($element));
    }

    /**
     * Get missing module names
     *
     * @return array
     */
    private function getMissingModuleNames(): array
    {
        return array_diff(self::VCT_MODULES, $this->fullModuleList->getNames());
    }

    /**
     * Get logo attributes
     *
     * @return array
     */
    private function getLogoAttributes(): array
    {
        $attributes = [];

        foreach ($this->getMissingModuleNames() as $moduleName) {
            $attributes[] = [
                'src' => sprintf(self::LOGO_URL_FORMAT, $moduleName),
                'href' => sprintf(self::MODULE_URL_FORMAT, strtolower(str_replace('_', '-', $moduleName))),
            ];
        }

        return $attributes;
    }
}
