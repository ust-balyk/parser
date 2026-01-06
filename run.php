<?php
set_time_limit(0);
ini_set('memory_limit', -1);
include __DIR__ . '/vendor/autoload.php';
include __DIR__ . '/funcs.php';
use GuzzleHttp\Client;
//use GuzzleHttp\Exception\ClientException;
//use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Middleware;
use GuzzleHttp\HandlerStack;
use DiDom\Document;
##########################################

$stack = HandlerStack::create();
$stack->push(Middleware::retry(
    function ($retries, $request, $response, $exception) {
        if ($response) {
            return $retries < 3 && $response->getStatusCode() >= 500;        
        }
        if ($exception instanceof ConnectException) {
            return $retries < 3;
        }
        return false;
    },
    function ($retries) { // Экспоненциальная задержка: 1s, 2s, 4s
        return 1000 * pow(2, $retries - 1);
    }
));

$client = new Client(['handler' => $stack]);
############################################

try {
    

           /*
        https://satomi-japan.com/productlist/new
        https://satomi-japan.com/productlist/best
        https://satomi-japan.com/productlist/sale
        https://satomi-japan.com/categories/v-nalichii
        https://satomi-japan.com/categories/zdorove
        https://satomi-japan.com/categories/kosmetika
        https://satomi-japan.com/categories/dlya-tela
        https://satomi-japan.com/categories/dlya-litsa
        https://satomi-japan.com/categories/dlya-detei-1
        https://satomi-japan.com/categories/dlya-muzhchin-1
        https://satomi-japan.com/categories/produkty-pitaniya
        https://satomi-japan.com/categories/tovary-dlya-doma
        https://satomi-japan.com/categories/pribory
        https://satomi-japan.com/categories/zootovary
        */

        if (! empty($argv[1])) {

            $url = $argv[1];
        
            $response = $client->request('GET', $url, [
                'timeout' => 5, //                        время ожидания ответа сервера
                'connect_timeout' => 5 //                  время на попытки подключения
            ]);
            echo "\nSuccess: " . $response->getStatusCode();
   
            $document = new Document();
            ###########################
        
            echo "\nStart parsing...\n";
            echo "* * * * * * * * * * * * * * * * * * * * *\n\n";
            $file = get_html($url, $client);
            $document->loadHtml($file);

            $array_products = [];
            $pages_count = get_pages_count($document);

            for ($i = 1; $i <= $pages_count; $i++) {
                echo "  PAGE PARSING {$i} of {$pages_count}...\n";
                if ($i > 1) {
                    $file = get_html($url . "?page={$i}", $client);
                    $document->loadHtml($file);
                }
                $array_products = array_merge($array_products, get_products($document, $client)); 
                sleep(rand(1, 3));

            // test
            /*
            if (! is_dir("products")) mkdir("products");
            $json_file = get_category(trim(substr($url, strrpos($url, '/')), '/'));
            file_put_contents("products/{$json_file}.json",
                json_encode($array_products, JSON_UNESCAPED_UNICODE|FILE_APPEND|JSON_PRETTY_PRINT));
             */
            }
            echo "\n* * * * * * * * * * * * * * * * * * * * *\n";
            //echo "Parsing done! ". count($array_products) ." products were analyzed.\n";
            echo "Анализ выполнен! Просканирован ". count($array_products) ." продукт.\n";

            if (count($array_products) > 0) {
            
                $pdo = new PDO('mysql:host=localhost;dbname=japan_in_ru', 'root', 'root');
                ##########################################################################
            
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $stmt = $pdo->prepare("INSERT IGNORE INTO cosmetics
                (id, category, slug, outer_id, title, description, image, price, old_price, new_price)
                VALUES (:id,:category,:slug,:outer_id,:title,:description,:image,:price,:old_price,:new_price)");
                foreach ($array_products as $product) {
                    $stmt->bindParam(':id', $product['id']);
                    $stmt->bindParam(':category', $product['category']);
                    $stmt->bindParam(':slug', $product['slug']);
                    $stmt->bindParam(':outer_id', $product['outer_id']);
                    $stmt->bindParam(':title', $product['title']);
                    $stmt->bindParam(':description', $product['description']);
                    $stmt->bindParam(':image', $product['image']);
                    $stmt->bindParam(':price', $product['price']);
                    $stmt->bindParam(':old_price', $product['old_price']);
                    $stmt->bindParam(':new_price', $product['new_price']);
                                                                          
                    $stmt->execute();  

            /* это обновит данные */
            //$stmt = $pdo->prepare("UPDATE products
            /*
            $stmt = $pdo->prepare("UPDATE cosmetics
                SET price = :price, old_price = :old_price, new_price = :new_price
                WHERE outer_id = :outer_id;");
            foreach ($array_products as $product) {
                $stmt->bindParam(':outer_id', $product['outer_id']);
                $stmt->bindParam(':price', $product['price']);
                $stmt->bindParam(':old_price', $product['old_price']);
                $stmt->bindParam(':new_price', $product['new_price']); 
                $stmt->execute();
                */

                }
            //echo "JSON array inserted into MySQL database successfully.\n";
                echo "Массив JSON успешно добавлен в базу данных MySQL.\n";
            }
        }
} catch (RequestException $e) { die ("* * * * E R R O R   R E Q U E S T * * * *\n"); }
