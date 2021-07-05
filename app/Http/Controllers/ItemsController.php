<?php

namespace App\Http\Controllers;

use App\Models\Items;
use Goutte\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ItemsController extends Controller
{
    private $image;
    private $description;
    private $url;
    private $price;
    private $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    public function getAllItems()
    {
        Log::info('Updated');
        $this->deleteOldItems();
        $itemsUrls = $this->getAllItemsUrls();
        foreach ($itemsUrls as $itemUrl) {
            try {
                $this->getItemDetails($itemUrl);
            } catch (\Exception $e) {
                continue;
            }
            $this->storeItem();
        }
    }

    public function deleteOldItems()
    {
        Items::query()->truncate();
    }

    public function getAllItemsUrls()
    {
        return DB::table('items_urls')->pluck('url');
    }

    public function getItemDetails($itemUrl)
    {
        $this->client->restart(); // removing all history and cookies from client to solve access denied problem
        $crawler = $this->client->request('GET', $itemUrl, [], [], ['HTTP_USER_AGENT' => "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.114 Safari/537.36"]);
        $test = $crawler->outerHtml();
        $this->image = $crawler->filter('.product-image__container img')->attr('src');
        $this->description = $crawler->filter('.product-details-tile__title')->text('Not Found');
        $this->price = $crawler->filter('.price-per-sellable-unit .value')->text(0);
        $this->url = $itemUrl;
    }

    public function storeItem()
    {
        Items::create([
            'image' => $this->image,
            'description' => $this->description,
            'price' => $this->price,
            'url' => $this->url
        ]);
    }

    public function getStoredItems()
    {
        $items = Items::query()->where('price', '!=', '0')->get();
        return response()->json($items);
    }
}
