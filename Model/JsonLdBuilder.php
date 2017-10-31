<?php

namespace Liip\OrderEmail\Model;

use Magento\Catalog\Api\ProductRepositoryInterfaceFactory;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\UrlInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\Information;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class JsonLdBuilder
{
    /**
     * @var ScopeConfigInterface
     */
    private $config;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ProductRepositoryInterfaceFactory
     */
    private $productRepositoryFactory;

    /**
     * @var Product\Url
     */
    private $productUrl;

    /**
     * @var Image
     */
    private $catalogImageHelper;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @param ScopeConfigInterface $config
     * @param StoreManagerInterface $storeManager
     * @param ProductRepositoryInterfaceFactory $productRepositoryFactory
     * @param Product\Url $productUrl
     * @param Image $catalogImageHelper
     * @param UrlInterface $urlBuilder
     * @param DateTime $dateTime
     */
    public function __construct(
        ScopeConfigInterface $config,
        StoreManagerInterface $storeManager,
        ProductRepositoryInterfaceFactory $productRepositoryFactory,
        \Magento\Catalog\Model\Product\Url $productUrl,
        Image $catalogImageHelper,
        UrlInterface $urlBuilder,
        DateTime $dateTime
    ) {
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->productRepositoryFactory = $productRepositoryFactory;
        $this->productUrl = $productUrl;
        $this->catalogImageHelper = $catalogImageHelper;
        $this->urlBuilder = $urlBuilder;
        $this->dateTime = $dateTime;
    }

    /**
     * @param Order $order
     * @return string
     */
    public function build(Order $order)
    {
        $orderViewUrl = $this->urlBuilder->getUrl('sales/order/view/', ['order_id' => $order->getEntityId()]);
        $context = (object)[
            "@context"           => "http://schema.org",
            "@type"              => "Order",
            "orderNumber"        => $order->getIncrementId(),
            "priceCurrency"      => $order->getBaseCurrencyCode(),
            "price"              => $order->getGrandTotal(),
            "acceptedOffer"      => $this->getOfferedItems($order),
            "url"                => $orderViewUrl,
            "orderStatus"        => "http://schema.org/OrderProcessing",
            "priceSpecification" => (object)[
                "@type"     => "PriceSpecification",
                "validFrom" => $this->dateTime->date("c"),
            ],
            "merchant" => (object)[
                "@type" => "Organization",
                "name"  => $this->getStoreName(),
            ],
            "potentialAction" => (object)[
                "@type" => "ViewAction",
                "url"   => $orderViewUrl,
            ],
        ];
        return json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @return string
     */
    private function getStoreName()
    {
        return $this->config->getValue(Information::XML_PATH_STORE_INFO_NAME, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @param Order $order
     * @return array|object
     */
    private function getOfferedItems(Order $order)
    {
        $offeredItems = [];

        foreach ($order->getItems() as $orderItem) {
            /** @var Product $product */
            $product = $this->productRepositoryFactory->create()->getById($orderItem->getProductId());
            $productImageUrl = $this->catalogImageHelper->init($product, 'category_page_grid')->getUrl();

            $offeredItems[] = (object)[
                "@type"            => "Offer",
                "price"            => $orderItem->getPrice(),
                "priceCurrency"    => $order->getBaseCurrencyCode(),
                "itemOffered"      => (object)[
                    "@type" => "Product",
                    "name"  => $orderItem->getName(),
                    "sku"   => $orderItem->getSku(),
                    "url"   => $this->productUrl->getProductUrl($product),
                    "image" => $productImageUrl,
                ],
                "eligibleQuantity" => (object)[
                    "@type" => "QuantitativeValue",
                    "value" => $orderItem->getQtyOrdered(),
                ],
            ];
        }
        if (count($offeredItems) == 1) {
            return $offeredItems[0];
        }
        return $offeredItems;
    }
}
