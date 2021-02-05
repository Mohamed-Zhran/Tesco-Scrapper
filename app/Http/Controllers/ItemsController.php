<?php

namespace App\Http\Controllers;

use App\Models\Items;
use Illuminate\Http\Request;
use Goutte\Client;
use Illuminate\Support\Facades\DB;

class ItemsController extends Controller
{
    private $image;
    private $description;
    private $url;
    private $price;

    public function getAllItemsUrls()
    {
        return DB::table('items_urls')->pluck('url');
    }

    public function getItemDetails($itemUrl)
    {
        $client = new Client();
        $crawler = $client->request('GET', $itemUrl, [], [], ['HTTP_USER_AGENT' => "Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/59.0.3071.115 Safari/537.36"]);
        $this->image = $crawler->filter('.product-image__container img')->attr('src');
        $this->description=$crawler->filter('.product-details-tile__title')->text('Not Found');
        $this->price=$crawler->filter('.price-per-sellable-unit .value')->text(0);
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

    public function deleteOldItems() {
        Items::query()->delete();
    }

    public function processAllItems()
    {
        $this->deleteOldItems();
        $itemsUrls = $this->getAllItemsUrls();
        foreach ($itemsUrls as $itemUrl)
        {
            $this->getItemDetails($itemUrl);
            $this->storeItem();
        }
    }

    public function getStoredItems()
    {
        $items = Items::all();
        return response()->json($items);
    }
}
