<?php
/**
 * Copyright (c) VCT. All rights reserved
 */
declare(strict_types=1);

namespace Vct\Main\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Helper\Js;

/**
 * @copyright Copyright (c) VCT. All rights reserved
 * @link https://vct-vendor.github.io
 * @phpcs:ignoreFile Magento2.Annotation.MethodAnnotationStructure.MethodAnnotation
 */
class Links extends Fieldset
{
    public const SUPPORT_INFO = 'support_info';
    private const LINKS_TEMPLATE = 'Vct_Main::links.phtml';

    /**
     * @var ProductMetadataInterface
     */
    private ProductMetadataInterface $productMetadata;

    /**
     * @var File
     */
    private File $file;

    /**
     * @var JsonSerializer
     */
    private JsonSerializer $jsonSerializer;

    /**
     * @var DirectoryList
     */
    private DirectoryList $directoryList;

    /**
     * @var HttpRequest
     */
    private HttpRequest $httpRequest;

    /**
     * @param Context $context
     * @param Session $authSession
     * @param Js $jsHelper
     * @param ProductMetadataInterface $productMetadata
     * @param File $file
     * @param JsonSerializer $jsonSerializer
     * @param DirectoryList $directoryList
     * @param HttpRequest $httpRequest
     */
    public function __construct(
        Context $context,
        Session $authSession,
        Js $jsHelper,
        ProductMetadataInterface $productMetadata,
        File $file,
        JsonSerializer $jsonSerializer,
        DirectoryList $directoryList,
        HttpRequest $httpRequest
    ) {
        parent::__construct($context, $authSession, $jsHelper);
        $this->productMetadata = $productMetadata;
        $this->file = $file;
        $this->jsonSerializer = $jsonSerializer;
        $this->directoryList = $directoryList;
        $this->httpRequest = $httpRequest;
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
        $supportInfos = $this->getSupportInfos();
        $html = $block->setTemplate(self::LINKS_TEMPLATE)->setData(self::SUPPORT_INFO, $supportInfos)->toHtml();

        return sprintf('%s%s', $html, parent::_getHeaderCommentHtml($element));
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    private function getSupportInfos(): array
    {
        $packageName = $this->getPackageName();
        $packageVersion = $this->getPackageVersion($packageName);
        $magentoEdition = $this->productMetadata->getEdition();
        $magentoVersion = $this->productMetadata->getVersion();

        return [
            'module' => sprintf('%s:%s', $packageName, $packageVersion),
            'magento' => sprintf('%s %s', $magentoEdition, $magentoVersion),
            'php' => sprintf('PHP %s', phpversion()),
        ];
    }

    /**
     * @return string
     */
    private function getPackageName(): string
    {
        $moduleConfigUrl = $this->httpRequest->getPathInfo();
        preg_match('/section\/(vct_[^\/]+)/', $moduleConfigUrl, $moduleName);

        return isset($moduleName[1]) ? str_replace('_', '/', $moduleName[1]) : 'N/A';
    }

    /**
     * @param string $packageName
     * @return string
     * @throws FileSystemException
     */
    private function getPackageVersion(string $packageName): string
    {
        $packagePath = $this->directoryList->getRoot();
        $composerLockPath = sprintf('%s/composer.lock', $packagePath);
        $composerLockContent = $this->file->fileGetContents($composerLockPath);
        $composerLockData = $this->jsonSerializer->unserialize($composerLockContent);
        $packages = $composerLockData['packages'];

        foreach ($packages as $package) {
            if ($package['name'] === $packageName) {
                return $package['version'];
            }
        }

        return 'N/A';
    }
}
