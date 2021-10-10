<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use App\Models\CurrencyExchange;

class CurrencyExchangeController extends Controller
{
    private $token;
    private $api;
    private $currencies;
    private $baseCurrency;
    private $exchangeRates;

    public function __construct()
    {
        $this->token = '2b94a7a5be8ab1c7c836645ba7cbbe92';
        $this->currencies = ['USD', 'EUR', 'CNY', 'JPY', 'GBP','AUD','CAD','CHF','HKD','NZD'];
        $this->api = "http://api.exchangeratesapi.io/latest?access_key=$this->token&symbols=" . implode(",", $this->currencies);
        $this->baseCurrency = 'GBP';
    }

    public function start()
    {
        $this->deleteExchangeRates();
        $this->setRates();
        $this->checkCurrencyExistence();
        $this->changeBase();
        $this->storeExchangeRates();
    }

    public function deleteExchangeRates()
    {
        CurrencyExchange::query()->truncate();
    }

    public function setRates()
    {
        $this->exchangeRates = $this->getApiRates();
    }

    public function getApiRates(): array
    {
        $response = Http::get($this->api);
        return $response->ok() ?
            json_decode($response->body(), true)['rates']
            : response()->json(['message' => 'failed to get the exchange rates']);
    }

    public function checkCurrencyExistence()
    {
        try {
            $this->isCurrencyExist();
        } catch (\InvalidArgumentException $e) {
            exit(response()->json($e->getMessage()));
        }
    }

    public function isCurrencyExist(): bool
    {
        if (!array_key_exists($this->baseCurrency, $this->exchangeRates)) {
            throw new \InvalidArgumentException("This base currency isn't found");
        }
        return true;
    }

    public function changeBase()
    {
        $newBaseRate = $this->exchangeRates[$this->baseCurrency];
        $conversionRate = 1 / $newBaseRate;
        foreach ($this->exchangeRates as &$rate) {
            $rate *= $conversionRate;
        }
    }

    public function storeExchangeRates()
    {
        foreach ($this->exchangeRates as $key => $exchangeRate) {
            CurrencyExchange::create([
                'name' => $key,
                'exchange_rate' => $exchangeRate
            ]);
        }
    }

    public function getPreparedExchangeRates(): array
    {
        $rates = [];
        foreach (CurrencyExchange::all(['name', 'exchange_rate']) as $value) {
            $rates[$value['name']] = $value['exchange_rate'];
        }
        return $rates;
    }

    public function sendExchangeRates(): \Illuminate\Http\JsonResponse
    {
        return response()->json($this->getPreparedExchangeRates());
    }
}
