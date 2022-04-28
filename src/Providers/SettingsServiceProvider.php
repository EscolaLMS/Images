<?php

namespace EscolaLms\Images\Providers;

use EscolaLms\Images\Enum\PackageStatusEnum;
use EscolaLms\Settings\EscolaLmsSettingsServiceProvider;
use EscolaLms\Settings\Facades\AdministrableConfig;
use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
{
    const CONFIG_KEY = 'images';

    public function register()
    {
        if (class_exists(\EscolaLms\Settings\EscolaLmsSettingsServiceProvider::class)) {
            if (!$this->app->getProviders(EscolaLmsSettingsServiceProvider::class)) {
                $this->app->register(EscolaLmsSettingsServiceProvider::class);
            }

            AdministrableConfig::registerConfig(self::CONFIG_KEY . '.private.rate_limiter_status', ['required', 'string', 'in:' . implode(',', PackageStatusEnum::getValues())], false);
            AdministrableConfig::registerConfig(self::CONFIG_KEY . '.private.rate_limit_global', ['required', 'numeric'], false);
            AdministrableConfig::registerConfig(self::CONFIG_KEY . '.private.rate_limit_per_ip', ['required', 'numeric'], false);
        }
    }
}
