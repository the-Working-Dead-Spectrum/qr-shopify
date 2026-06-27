<?php

use App\Providers\AppServiceProvider;
use App\Providers\DomainServiceProvider;
use App\Providers\ShopifyServiceProvider;

return [
    AppServiceProvider::class,
    DomainServiceProvider::class,
    ShopifyServiceProvider::class,
];