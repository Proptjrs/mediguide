<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Collection;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\BeforeClass;

/**
 * Base des tests E2E Dusk (mémoire, chap. 4.6.3).
 *
 * Par défaut : Chrome + ChromeDriver (`php artisan dusk:chrome-driver`).
 * Sur un poste sans Chrome, on peut piloter Edge (préinstallé sous Windows) en
 * ajoutant à `.env.dusk.local` :
 *
 *   DUSK_BROWSER=edge
 *   DUSK_DRIVER_URL=http://localhost:9515
 *   DUSK_BROWSER_BINARY="C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe"
 *
 * puis en lançant le driver correspondant : `msedgedriver --port=9515`
 * (téléchargeable sur https://developer.microsoft.com/microsoft-edge/tools/webdriver/,
 * la version doit correspondre à celle d'Edge).
 */
abstract class DuskTestCase extends BaseTestCase
{
    /**
     * Prépare l'exécution des tests Dusk.
     *
     * Si DUSK_DRIVER_URL est défini, on suppose qu'un WebDriver tourne déjà.
     */
    #[BeforeClass]
    public static function prepare(): void
    {
        if (static::runningInSail() || env('DUSK_DRIVER_URL')) {
            return;
        }

        static::startChromeDriver(['--port=9515']);
    }

    /**
     * Crée l'instance RemoteWebDriver.
     */
    protected function driver(): RemoteWebDriver
    {
        $arguments = collect([
            $this->shouldStartMaximized() ? '--start-maximized' : '--window-size=1920,1080',
            '--disable-search-engine-choice-screen',
            '--disable-smooth-scrolling',
        ])->unless($this->hasHeadlessDisabled(), function (Collection $items) {
            return $items->merge([
                '--disable-gpu',
                '--headless=new',
            ]);
        })->all();

        $options = (new ChromeOptions)->addArguments($arguments);

        // Binaire Chromium personnalisé (Edge, Brave, Chromium…) sans toucher à ce fichier.
        if ($binary = env('DUSK_BROWSER_BINARY')) {
            $options->setBinary($binary);
        }

        // msedgedriver attend browserName=MicrosoftEdge et la clé ms:edgeOptions ;
        // les capacités Chrome sont rejetées ("No matching capabilities found").
        $capabilities = env('DUSK_BROWSER') === 'edge'
            ? DesiredCapabilities::microsoftEdge()->setCapability('ms:edgeOptions', $options)
            : DesiredCapabilities::chrome()->setCapability(ChromeOptions::CAPABILITY, $options);

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? env('DUSK_DRIVER_URL') ?? 'http://localhost:9515',
            $capabilities
        );
    }
}
