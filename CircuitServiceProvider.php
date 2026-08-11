<?php

namespace GeneralPurposeIO\Circuits;

use Fabricate\Chassis\Exceptions\BindingResolutionException;
use Fabricate\Contracts\Core\Program;
use Fabricate\Core\Console\AboutCommand;
use Fabricate\Core\Machine as ScrapyardIOMachine;
use Fabricate\NutsAndBolts\Contracts\DeferrableProvider;
use Fabricate\NutsAndBolts\ServiceProvider;
use GeneralPurposeIO\Circuits\Console\CircuitMakeProfileCommand;
use GeneralPurposeIO\Circuits\Enums\CircuitConsoleCommand;
use GeneralPurposeIO\Circuits\Support\CircuitCatalogAboutRows;
use GeneralPurposeIO\Contracts\Circuits\CircuitRegistry as RegistryContract;

class CircuitServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * @throws BindingResolutionException
     */
    public function register(): void
    {
        $this->publishConfig();

        $this->container->singleton('circuit', fn (Program $program) => new CircuitRegistry);
        $this->container->alias('circuit', CircuitRegistry::class);
        $this->container->alias('circuit', RegistryContract::class);

        $this->container->singleton(CircuitMakeProfileCommand::class);
        $this->commands([
            CircuitMakeProfileCommand::class,
        ]);
    }

    public function boot(): void
    {
        $this->registerAboutSection();
    }

    /**
     * Contribute catalog IC inventory to Workshop `about`.
     *
     * Rows resolve at `about` run-time (after IC packages register), not at boot.
     * Catalog only — never config/circuits.php profiles.
     */
    protected function registerAboutSection(): void
    {
        if (! class_exists(AboutCommand::class)) {
            return;
        }

        AboutCommand::add('Integrated Circuits', function (): array {
            /** @var CircuitRegistry $registry */
            $registry = $this->container->make(CircuitRegistry::class);

            return CircuitCatalogAboutRows::for($registry);
        });
    }

    /**
     * @throws BindingResolutionException
     */
    protected function publishConfig(): void
    {
        $source = realpath($raw = __DIR__.'/../../../config/circuits.php') ?: $raw;

        if ($this->container instanceof ScrapyardIOMachine && $this->container->runningInConsole()) {
            $this->publishes(
                [$source => $this->container->configPath('circuits.php')],
                CircuitConsoleCommand::PUBLISH_CONFIG_TAG->value,
            );
        }

        $this->mergeConfigFrom($source, 'circuits');
    }

    public function provides(): array
    {
        return [
            'circuit',
            CircuitMakeProfileCommand::class,
        ];
    }
}
