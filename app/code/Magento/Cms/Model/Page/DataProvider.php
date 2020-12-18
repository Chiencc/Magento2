<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cms\Model\Page;

use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Model\Page;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use Magento\Ui\DataProvider\ModifierPoolDataProvider;
use Psr\Log\LoggerInterface;

/**
 * Cms Page DataProvider
 */
class DataProvider extends ModifierPoolDataProvider
{
    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var array
     */
    protected $loadedData;

    /**
     * @var PageRepositoryInterface
     */
    private $pageRepository;

    /**
     * @var AuthorizationInterface
     */
    private $auth;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var CustomLayoutManagerInterface
     */
    private $customLayoutManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $pageCollectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param array $meta
     * @param array $data
     * @param PoolInterface|null $pool
     * @param AuthorizationInterface|null $auth
     * @param RequestInterface|null $request
     * @param CustomLayoutManagerInterface|null $customLayoutManager
     * @param PageRepositoryInterface|null $pageRepository
     * @param LoggerInterface|null $logger
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $pageCollectionFactory,
        DataPersistorInterface $dataPersistor,
        array $meta = [],
        array $data = [],
        PoolInterface $pool = null,
        ?AuthorizationInterface $auth = null,
        ?RequestInterface $request = null,
        ?CustomLayoutManagerInterface $customLayoutManager = null,
        ?PageRepositoryInterface $pageRepository = null,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data, $pool);
        $this->collection = $pageCollectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        $this->auth = $auth ?? ObjectManager::getInstance()->get(AuthorizationInterface::class);
        $this->meta = $this->prepareMeta($this->meta);
        $this->request = $request ?? ObjectManager::getInstance()->get(RequestInterface::class);
        $this->customLayoutManager = $customLayoutManager
            ?? ObjectManager::getInstance()->get(CustomLayoutManagerInterface::class);
        $this->pageRepository = $pageRepository ?? ObjectManager::getInstance()->get(PageRepositoryInterface::class);
        $this->logger = $logger ?: ObjectManager::getInstance()->get(LoggerInterface::class);
    }

    /**
     * Prepares Meta
     *
     * @param array $meta
     * @return array
     */
    public function prepareMeta(array $meta)
    {
        return $meta;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        try {
            $page = $this->getCurrentPage();
        } catch (LocalizedException $exception) {
            return [];
        }

        $pageId = $page->getId();
        $this->loadedData[$pageId] = $page->getData();
        if ($page->getCustomLayoutUpdateXml() || $page->getLayoutUpdateXml()) {
            //Deprecated layout update exists.
            $this->loadedData[$pageId]['layout_update_selected'] = '_existing_';
        }

        $data = $this->dataPersistor->get('cms_page');
        if (empty($data)) {
            return $this->loadedData;
        }

        $page = $this->collection->getNewEmptyItem();
        $page->setData($data);
        $this->loadedData[$pageId] = $page->getData();
        if ($page->getCustomLayoutUpdateXml() || $page->getLayoutUpdateXml()) {
            $this->loadedData[$pageId]['layout_update_selected'] = '_existing_';
        }
        $this->dataPersistor->clear('cms_page');

        return $this->loadedData;
    }

    /**
     * Loads the current page by current request params.
     *
     * @return Page
     * @throws LocalizedException
     */
    private function getCurrentPage(): Page
    {
        $pageId = $this->request->getParam($this->getRequestFieldName(), 0);

        return $this->pageRepository->getById($pageId);
    }

    /**
     * @inheritDoc
     */
    public function getMeta()
    {
        $meta = parent::getMeta();

        if (!$this->auth->isAllowed('Magento_Cms::save_design')) {
            $designMeta = [
                'design' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'disabled' => true
                            ]
                        ]
                    ]
                ],
                'custom_design_update' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'disabled' => true
                            ]
                        ]
                    ]
                ]
            ];
            $meta = array_merge_recursive($meta, $designMeta);
        }

        //List of custom layout files available for current page.
        $options = [['label' => 'No update', 'value' => '_no_update_']];

        $page = null;
        try {
            $page = $this->getCurrentPage();
        } catch (LocalizedException $e) {
            $this->logger->error($e->getMessage());
        }

        if ($page) {
            if ($page->getCustomLayoutUpdateXml() || $page->getLayoutUpdateXml()) {
                $options[] = ['label' => 'Use existing layout update XML', 'value' => '_existing_'];
            }
            foreach ($this->customLayoutManager->fetchAvailableFiles($page) as $layoutFile) {
                $options[] = ['label' => $layoutFile, 'value' => $layoutFile];
            }
        }

        $customLayoutMeta = [
            'design' => [
                'children' => [
                    'custom_layout_update_select' => [
                        'arguments' => [
                            'data' => ['options' => $options]
                        ]
                    ]
                ]
            ]
        ];
        $meta = array_merge_recursive($meta, $customLayoutMeta);

        return $meta;
    }
}
