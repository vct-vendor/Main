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
use Magento\Framework\Shell;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Helper\Js;

/**
 * @copyright Copyright (c) VCT
 * @link https://vct-vendor.github.io
 */
class Links extends Fieldset
{
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
     * @param Shell $shell
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
        Shell $shell,
        File $file,
        JsonSerializer $jsonSerializer,
        DirectoryList $directoryList,
        HttpRequest $httpRequest
    ) {
        parent::__construct($context, $authSession, $jsHelper);
        $this->productMetadata = $productMetadata;
        $this->shell = $shell;
        $this->file = $file;
        $this->jsonSerializer = $jsonSerializer;
        $this->directoryList = $directoryList;
        $this->httpRequest = $httpRequest;
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
            ->setData('support_info', $this->getSupportInfo())
            ->setTemplate('Vct_Main::links.phtml')
            ->toHtml();

        return sprintf('%s%s', $html, parent::_getHeaderCommentHtml($element));
    }

    /**
     * Get information for support
     *
     * @return array
     * @throws LocalizedException
     */
    private function getSupportInfo(): array
    {
        $packageName = $this->getPackageName();
        $packageVersion = $this->getPackageVersion($packageName);

        return [
            'module' => sprintf('%s:%s', $packageName, $packageVersion),
            'magento' => sprintf('%s %s', $this->productMetadata->getEdition(), $this->productMetadata->getVersion()),
            'php' => sprintf('PHP %s', phpversion()),
        ];
    }

    /**
     * Get package name
     *
     * @return string
     */
    private function getPackageName(): string
    {
        preg_match('/\/section\/([^\/]+)\//', $this->httpRequest->getPathInfo(), $moduleUrlPath);

        return isset($moduleUrlPath[1]) ? str_replace('_', '/', $moduleUrlPath[1]) : 'N/A';
    }

    /**
     * Get package version
     *
     * @param string $packageName
     * @return string
     * @throws FileSystemException
     */
    private function getPackageVersion(string $packageName): string
    {
        $composerLockPath = sprintf('%s/composer.lock', $this->directoryList->getRoot());
        $composerLock = $this->file->fileGetContents($composerLockPath);
        $composerLockData = $this->jsonSerializer->unserialize($composerLock);

        foreach ($composerLockData['packages'] as $package) {
            if ($package['name'] === $packageName) {
                return $package['version'];
            }
        }

        return 'N/A';
    }
}
