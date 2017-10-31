# magento-order-email-jsonld

## Overview
This extension provides an email variable, which contains JSON-LD object, so the 'new order' emails could inject it.

## Setup

### Installation

1. `composer require liip/magento-order-email-jsonld`
2. `bin/magento setup:upgrade`
3. `bin/magento cache:clean`

### Configuration

In your 'new order' email template add following line:
```html
<script type="application/ld+json">{{var json_ld|raw}}</script>
```
