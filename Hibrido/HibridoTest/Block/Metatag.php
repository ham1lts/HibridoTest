<?php
namespace Hibrido\HibridoTest\Block;

use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Helper\Page;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Api\Data\StoreInterface;


class Metatag extends Template
{
    /** @var PageInterface */
    protected $page;

    /** @var Resolver */
    protected $localeResolver;

    /** @var PageRepositoryInterface */
    protected $pageRepository;

    /** @var Page */
    protected $cmsPageHelper;

    /**
     * Metatag constructor.
     * @param Context $context
     * @param Resolver $localeResolver
     * @param PageRepositoryInterface $pageRepository
     * @param Page $cmsPageHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Resolver $localeResolver,
        PageRepositoryInterface $pageRepository,
        Page $cmsPageHelper,
        array $data = []
    ) {
        $this->localeResolver = $localeResolver;
        $this->pageRepository = $pageRepository;
        $this->cmsPageHelper = $cmsPageHelper;
        parent::__construct($context, $data);
    }

    /**
     * Verifica se a página é CMS e usada em mais de uma loja
     * depois passa no foreach concatenando todas as metatags em apenas uma variável
     * @return string
     */
    protected function _toHtml()
    {
        if($this->isPageCms() && $this->isPageUsedInMultiStores()) {
            $metaTagHtml = '';
            foreach ($this->_storeManager->getStores() as $store) {
                $storeBaseUrl = $store->getBaseUrl();
                $metaTagHtml .= '<link rel="alternate" hreflang="' . $this->getStoreLanguage($store) . '" href="' . $storeBaseUrl . $this->page->getIdentifier() . '" />';
            }
            return $metaTagHtml;
        }

        return '';
    }

    /**
     * Verifica o idioma da loja dentro do core_config_data
     * @param StoreInterface $store
     * @return mixed
     */
    public function getStoreLanguage($store) {
        $locale = $this->_scopeConfig->getValue('general/locale/code', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $store->getId());
        return $locale;
    }

    /**
     * @return bool
     */
    protected function isPageCms() {
        $moduleName = $this->getRequest()->getModuleName();
        if($moduleName === 'cms' && $this->getRequest()->getParam('page_id')) {
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    protected function isPageUsedInMultiStores() {
        try {
            $this->page = $this->pageRepository->getById($this->getRequest()->getParam('page_id'));

            if (count($this->page->getStoreId()) > 1 || $this->page->getStoreId(0) === '0') {
                $stores = $this->_storeManager->getStores();
                return count($stores) > 1;
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

}
