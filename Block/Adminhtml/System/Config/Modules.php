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
 * @phpcs:ignoreFile Magento2.Annotation.MethodAnnotationStructure.MethodAnnotation
 */
class Modules extends Fieldset
{
    public const LOGO_URL = 'logo_url';
    public const MODULE_URL = 'module_url';
    public const MODULES_DATA = 'modules_data';
    private const LOGO_URL_FORMAT = 'https://raw.githubusercontent.com/vct-vendor/vct-vendor.github.io/master/static/img/docs/%s.svg'; /* phpcs:ignore Generic.Files.LineLength.TooLong */
    private const MODULE_URL_FORMAT = 'https://marketplace.magento.com/%s.html#maincontent';
    private const MODULES_TEMPLATE = 'Vct_Main::modules.phtml';
    private const VCT_MODULES = [
        'vct_changeskudynamically',
        'vct_simpleproducturl',
        'vct_productinfoswitcher',
        'vct_alsobought',
        'vct_pricediff',
        'vct_placeordersidebar',
    ];

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
     * @param AbstractElement $element
     * @return string
     * @throws LocalizedException
     * @SuppressWarnings(CamelCaseMethodName)
     */
    protected function _getHeaderCommentHtml($element): string
    {
        if ($element->getData('comment')) {
            return parent::_getHeaderCommentHtml($element);
        }

        /** @var Template $block */
        $block = $this->getLayout()->createBlock(Template::class);
        $modulesData = $this->getModulesData();
        $html = $block->setTemplate(self::MODULES_TEMPLATE)->setData(self::MODULES_DATA, $modulesData)->toHtml();

        return sprintf('%s%s', $html, parent::_getHeaderCommentHtml($element));
    }

    /**
     * @return array
     */
    private function getMissingModuleNames(): array
    {
        $moduleNameList = $this->fullModuleList->getNames();

        return array_diff(self::VCT_MODULES, array_map('strtolower', $moduleNameList));
    }

    /**
     * @return array
     */
    private function getModulesData(): array
    {
        $data = [];

        foreach ($this->getMissingModuleNames() as $moduleName) {
            $packageName = strtolower(str_replace('_', '-', $moduleName));
            $moduleUrl = sprintf(self::MODULE_URL_FORMAT, $packageName);
            $logoUrl = sprintf(self::LOGO_URL_FORMAT, $moduleName);
            $data[] = [self::MODULE_URL => $moduleUrl, self::LOGO_URL => $logoUrl];
        }

        return $data;
    }
}
