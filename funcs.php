<?php

function get_html($url, \GuzzleHttp\Client $client)
{
    $response = $client->get($url);
    return $response->getBody()->getContents();

}

function get_pages_count(\DiDom\Document $document)
{
    $pagination = $document->find('.pagenumberer .pagenumberer-item');    
    if (count($pagination) > 1) {
        return $pagination[count($pagination)-1]->text();
    } else {
        return 1;
    }
    return count($pagination);

}

function get_products(\DiDom\Document $document, \GuzzleHttp\Client $client)
{
    static $product_number = 1;
    $array_products = [];
    $products = $document->find('.products-view-item');
    if (count($products) > 1) {
        echo "  " . count($products) . " products found:\n";
    } else {
        echo "  " . count($products) . " product found:\n";
    }
    
    foreach ($products as $product) {
        echo "Product analysis number {$product_number}...\n";
        $link = $product->first('.products-view-name-link')->attr('href');
        $array_products[$product_number] = get_product($document, $client, $link);
        sleep(rand(1, 3));
        $product_number++;

    }
    return $array_products;

}

function get_product(\DiDom\Document $document, \GuzzleHttp\Client $client, $link)
{
    $product_card = get_html($link, $client);
    $document->loadHtml($product_card);

    global $url;

    $category = trim(substr($url, strrpos($url, '/')), '/');
    $product['category'] = get_category($category);

    $slug = trim(substr($url, strrpos($url, '/')), '/');
    $product['slug'] = get_slug($slug);
    
    $product['outer_id'] = $document->first('.cart-add')->attr('data-product-id');
    
    $product['title'] = preg_replace("~\r|\n|\s{2,}~", "", $document->first('.details-title')->text());
    
    $product['description'] = "";
    if ($document->has('.accordion-css__body')) {
        $product['description'] = preg_replace("~\r|\n|\s{2,}~", "",
                                    $document->first('.accordion-css__body')->text());
    }
    

    $product['price'] = preg_replace("~\s~", "", $document->find('.price')[0]->text());

    /*
    if ($document->has('.price .price-old')) {
        $product['old_price'] = preg_replace("~\s~", "", $document->find('.price-old')[0]->text());
        $product['new_price'] = preg_replace("~\s~", "", $document->find('.price-new')[0]->text());
    } else {
        $product['old_price'] = "";
        $product['new_price'] = "";
    }*/

    $product['image'] = "";
    if ($document->has('.gallery-picture-obj')) {
        $image_path = $document->first('.gallery-picture-obj')->attr('src');
        $image_name = $product['outer_id'] . image_extension($image_path);
        $image_dir = $product['category'];

        if (! is_dir("images/{$image_dir}")) mkdir("images/{$image_dir}", 0755, true);
        file_put_contents("images/{$image_dir}/{$image_name}",file_get_contents($image_path));
        $product['image'] = "images/{$image_dir}/{$image_name}";
        /*
        if (! is_dir("../master/public/images/{$image_dir}")) {
            mkdir("../master/public/images/{$image_dir}", 0755, true);
        }
        file_put_contents("../master/public/images/{$image_dir}/{$image_name}",
            file_get_contents($image_path));
        $product['image'] = "/public/images/{$image_dir}/{$image_name}";
        */
    
    }
    return $product;

}

function image_extension($image_path)
{
    $image_extension = substr($image_path, strrpos($image_path, '.'));
    return $image_extension; // точка будет сохранена .jpeg

}

function get_category($target) // замена названия категории
{
    $categories = [
        "В наличии"           => "v-nalichii",
        "Новинки"             => "new",
        "Популярные товары"   => "best",
        "Товары по акции"     => "sale",
        "Твоё здоровье"       => "zdorove",
        "Японская косметика"  => "kosmetika",
        "Для мужчин"          => "dlya-muzhchin-1",
        "Для детей"           => "dlya-detei-1",
        "Продукты питания"    => "produkty-pitaniya",
        "Товары для дома"     => "tovary-dlya-doma",
        "Приборы и массажеры" => "pribory",
        "Зоотовары"           => "zootovary",

        /* cosmetics */
        "декоративная косметика" => "dekorativnaya-kosmetika",
        "для лица"               => "dlya-litsa",
        "для полости рта"        => "dlya-polosti-rta",
        "для волос"              => "dlya-volos",
        "для тела"               => "dlya-tela",
        "для рук"                => "dlya-ruk",
        "для ног"                => "dlya-nog",
        "ароматерапия"           => "aromadiffuzory-dlya-doma",
        "подарочные наборы"      => "nabory-dlya-podarkov",
        "аксессуары"             => "aksessuary",

    ];

    $category = $target;
    foreach ($categories as $key => $value) {    
        if ($value === $target) {
            $category = $key;
        }
    }
    return $category;

}

function get_slug($target)
{
    $arr_slug = [
        "in-stock"        => "v-nalichii",
        "new"             => "new",
        "hit-sales"       => "best",
        "promo"           => "sale",
        "bless-you"       => "zdorove",
        "cosmetic"        => "kosmetika",
        "for-men"         => "dlya-muzhchin-1",
        "for-children"    => "dlya-detei-1",
        "foodstuffs"      => "produkty-pitaniya",
        "household-goods" => "tovary-dlya-doma",
        "devices"         => "pribory",
        "pet-supplies"    => "zootovary",

        /* cosmetics */
        "makeup"          => "dekorativnaya-kosmetika",
        "for-body"        => "dlya-tela",
        "for-face"        => "dlya-litsa",
        "for-oral-cavity" => "dlya-polosti-rta",
        "for-hair"        => "dlya-volos",
        "for-hands"       => "dlya-ruk",
        "for-feet"        => "dlya-nog",
        "aromatherapy"    => "aromadiffuzory-dlya-doma",
        "sets-gift"       => "nabory-dlya-podarkov",
        "accessories"     => "aksessuary",
    ];
    
    $slug = $target;
    foreach ($arr_slug as $key => $value) {
        if ($value === $target) {
            $slug = $key;
        }
    }
    return $slug;

}
