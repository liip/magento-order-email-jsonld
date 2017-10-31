<?php

namespace Liip\OrderEmail\Observer;

use Liip\OrderEmail\Model\JsonLdBuilder;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class BeforeOrderEmailSend implements ObserverInterface
{
    /**
     * @var JsonLdBuilder
     */
    private $jsonLdBuilder;

    /**
     * @param JsonLdBuilder $jsonLdBuilder
     */
    public function __construct(JsonLdBuilder $jsonLdBuilder)
    {
        $this->jsonLdBuilder = $jsonLdBuilder;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Framework\DataObject $transport */
        $transport = $observer->getData('transport');
        $transport->setData('json_ld', $this->jsonLdBuilder->build($transport->getData('order')));
    }
}
