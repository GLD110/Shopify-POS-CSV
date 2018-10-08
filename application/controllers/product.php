<?php //if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Product extends MY_Controller {

  public function __construct() {
    parent::__construct();
    $this->load->model( 'Product_model' );
    ini_set('max_execution_time', 36000);
  }

  public function index(){
    $this->is_logged_in();
    $this->manage();
  }

  public function manage( $page =  0 ){
    // Check the login
    $this->is_logged_in();

    $this->load->view('view_header');
    $this->load->view('view_product');
    $this->load->view('view_footer');
  }

  function csv_to_array($filename='', $delimiter=',')
  {
  	if(!file_exists($filename) || !is_readable($filename))
  		return FALSE;

  	$header = NULL;
  	$data = array();
  	if (($handle = $this->utf8_fopen_read($filename, 'r')) !== FALSE)
  	{
  		while (($row = fgetcsv($handle, 10000, $delimiter)) !== FALSE)
  		{
  			if(!$header)
  				$header = $row;
  			else
          $data[] = array_combine($header, $row);
  		}
  		fclose($handle);
  	}
  	return $data;
  }

  function utf8_fopen_read($fileName) {
    $fc = iconv('windows-1250', 'utf-8', file_get_contents($fileName));
    $handle=fopen("php://memory", "rw");
    fwrite($handle, $fc);
    fseek($handle, 0);
    return $handle;
  }

  public function update_pos()
  {
    // Check the login
    $this->is_logged_in();

    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, GET");

    if(isset( $_GET[ "file_name" ]))
    {
      $base_url = $this->config->item('base_url');
      $app_path = $this->config->item('app_path');

      //Import Product array from CSV
      $pos_brands = $this->csv_to_array($this->config->item('app_path') . 'uploads/csv/' . 'brands.csv');
      $pos_products = $this->csv_to_array($this->config->item('app_path') . 'uploads/csv/' . $_GET[ "file_name" ]);

      $this->load->model( 'Shopify_model' );
      $this->_default_store = $this->config->item('PRIVATE_SHOP');
      $this->Shopify_model->setStore( $this->_default_store, $this->_arrStoreList[$this->_default_store]->app_id, $this->_arrStoreList[$this->_default_store]->app_secret );
      $pos_tag = $_GET[ "pos_tag" ];
      set_time_limit(0);
      $pos1 = $pos_products[0];

      foreach($pos_brands as $brand){
        if(strpos($brand['Brand'], '(') !== 0 ){
          $url = 'https://sdc.semadatacoop.org/sdcapi/export/digitalassets';

          if(strpos($brand['Brand'], '/') !== false){
            $brands = explode("/",$brand['Brand']);
            foreach($brands as $b){
              $strParam = json_encode( array('token'=>'EAAAAIkm2ddJGVus4iRRFgKjJ6jj3EUNUZTEV7kxJR3fQl4g', 'aaia_brandid'=>$b) );
              // Init the session
              $curl = curl_init();

              // Set configuration value
              curl_setopt($curl, CURLOPT_URL, $url );                                         // Required : Set the access url
              curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');      // Optional : Set POST
              if( $strParam != '' ) curl_setopt($curl, CURLOPT_POSTFIELDS, $strParam);        // Required : POST Parameter String
              curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1 );                                 // Required : Enable the HTTP response as return value
              curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0 );                                 // Required : Ignore the SSL Certificate Verify

              $header = array(
                'Content-Type: application/json',
                'Accept: application/json',
                'Content-Length: ' . strlen($strParam)
              );

              curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

              // Access the remote URL
              $result = curl_exec($curl);
              $error_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

              // Close the session
              curl_close($curl);

              $DigitalAssets = json_decode($result)->DigitalAssets;

              foreach($DigitalAssets as $asset)
              foreach($pos_products as $pos)
              {
                if($pos['Brand'] == $brand['Brand'])
                if($pos['ManufacturerPartNo'] == $asset->PartNumber && $asset->AssetTypeCode != 'LGO'){
                $action = 'products.json';

                $products_array = array(
                    'product' => array(
                        "title" => $pos['LongDescription'],
                        'body_html' => "<p>" . $pos['LongDescription'] . "<\/p>",
                        "vendor" => $pos['VendorName'],
                        "product_type" => $pos['ManufacturerPartNo'],
                        "tags" => $b['Brand'],
                        "images" => array(
                          array("src" => $asset->Link)
                        ),
                        'variants' => array(
                          array(
                            "price" => $pos['Cost'],
                            "sku" => $pos['VenCode'] . $pos['PartNumber']
                          )
                        ),
                    )
                );

                // Retrive Data from Shop
                $productInfo = $this->Shopify_model->accessAPI( $action, $products_array, 'POST' );

                if(!isset($productInfo->product)){
                  var_dump("error" . '-' . $productInfo->errors->product);
                }
                else{
                  var_dump("success" . '-' . $productInfo->product->handle);
              }
            }
          }
              echo "POS Updated";
            }
        }
          else{
            $strParam = json_encode( array('token'=>'EAAAAIkm2ddJGVus4iRRFgKjJ6jj3EUNUZTEV7kxJR3fQl4g', 'aaia_brandid'=>$brand['Brand']) );
            // Init the session
            $curl = curl_init();

            // Set configuration value
            curl_setopt($curl, CURLOPT_URL, $url );                                         // Required : Set the access url
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');      // Optional : Set POST
            if( $strParam != '' ) curl_setopt($curl, CURLOPT_POSTFIELDS, $strParam);        // Required : POST Parameter String
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1 );                                 // Required : Enable the HTTP response as return value
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0 );                                 // Required : Ignore the SSL Certificate Verify

            $header = array(
              'Content-Type: application/json',
              'Accept: application/json',
              'Content-Length: ' . strlen($strParam)
            );

            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

            // Access the remote URL
            $result = curl_exec($curl);
            $error_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            // Close the session
            curl_close($curl);

            $DigitalAssets = json_decode($result)->DigitalAssets;

            foreach($DigitalAssets as $asset)
            foreach($pos_products as $pos)
            {
              if($pos['Brand'] == $brand['Brand'])
              if($pos['ManufacturerPartNo'] == $asset->PartNumber && $asset->AssetTypeCode != 'LGO'){
              $action = 'products.json';

              $products_array = array(
                  'product' => array(
                      "title" => $pos['LongDescription'],
                      'body_html' => "<p>" . $pos['LongDescription'] . "<\/p>",
                      "vendor" => $pos['VendorName'],
                      "product_type" => $pos['ManufacturerPartNo'],
                      "tags" => $brand['Brand'],
                      "images" => array(
                        array("src" => $asset->Link)
                      ),
                      'variants' => array(
                        array(
                          "price" => $pos['Cost'],
                          "sku" => $pos['VenCode'] . $pos['PartNumber']
                        )
                      ),
                  )
              );

              // Retrive Data from Shop
              $productInfo = $this->Shopify_model->accessAPI( $action, $products_array, 'POST' );

              if(!isset($productInfo->product)){
                var_dump("error" . '-' . $productInfo->errors->product);
              }
              else{
                var_dump("success" . '-' . $productInfo->product->handle);
              }
            }
          }
            echo "POS Updated";
          }
      }
    }
  }
  }

  public function update_pos1()
  {
    // Check the login
    $this->is_logged_in();

    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST");

    if(isset( $_GET[ "file_name" ]))
    {
      $base_url = $this->config->item('base_url');
      $app_path = $this->config->item('app_path');

      //Import Product array from CSV
      $pos_products = $this->csv_to_array($this->config->item('app_path') . 'uploads/csv/' . $_GET[ "file_name" ]);

      $this->load->model( 'Shopify_model' );
      $this->Shopify_model->setStore( $this->_default_store, $this->_arrStoreList[$this->_default_store]->app_id, $this->_arrStoreList[$this->_default_store]->app_secret );
      $pos_tag = $_GET[ "pos_tag" ];
      set_time_limit(0);
      $pos = $pos_products[0];
      foreach($pos_products as $pos)
      {
        $action = 'products.json?fields=id,tags&' . 'handle=' . $pos['Handle'];
        $productInfo = $this->Shopify_model->accessAPI( $action );
        $product = $productInfo->products[0];
        $tags = $product->tags;
        if($tags == ""){
          $tags = $pos_tag;
        }
        else {
          $tags = $tags . ', ' . $pos_tag;
        }

        $action = 'products/' . $product->id . '.json';
        $products_array = array(
            'product' => array(
                "id" => $product->id,
                "tags" => $tags
            )
        );
        // Retrive Data from Shop
        //$update_productInfo = $this->Shopify_model->accessAPI( $action, $products_array, 'PUT' );

        if(!isset($update_productInfo->product)){
          var_dump("error" . '-' .$pos['Handle']);
        }
        else{
          var_dump("success" . '-' . $pos['Handle']);
        }
      }
      echo "POS Updated";
    }
  }

  public function upload_csv()
  {
    // Check the login
    $this->is_logged_in();

    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST");

    $data = array();
    $base_url = $this->config->item('base_url');
    $app_path = $this->config->item('app_path');

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
       if($_FILES['file']['name'] == '') {
          echo "Please choose the csv file !";
      }
      else{
          $name     = $_FILES['file']['name'];
          $tmpName  = $_FILES['file']['tmp_name'];
          $error    = $_FILES['file']['error'];
          $size     = $_FILES['file']['size'];
          $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

          switch ($error) {
              case UPLOAD_ERR_OK:
                  $valid = true;
                  //validate file extensions
                  if ( !in_array($ext, array('csv')) ) {
                      $valid = false;
                      $response = 'Invalid file extension.';
                  }
                  //validate file size
                  if ( $size/1024/1024 > 50 ) {
                      $valid = false;
                      $response = 'File size is exceeding maximum allowed size.';
                  }
                  //upload file
                  if ($valid) {
                      $upload_dir = $app_path . 'uploads/csv/';
                      $targetPath =  $upload_dir . $name;
                      move_uploaded_file($tmpName, $targetPath);
                      $response = $name;
                  }
                  break;
              case UPLOAD_ERR_INI_SIZE:
                  $response = 'The uploaded file exceeds the upload_max_filesize directive in php.ini.';
                  break;
              case UPLOAD_ERR_PARTIAL:
                  $response = 'The uploaded file was only partially uploaded.';
                  break;
              case UPLOAD_ERR_NO_FILE:
                  $response = 'No file was uploaded.';
                  break;
              case UPLOAD_ERR_NO_TMP_DIR:
                  $response = 'Missing a temporary folder. Introduced in PHP 4.3.10 and PHP 5.0.3.';
                  break;
              case UPLOAD_ERR_CANT_WRITE:
                  $response = 'Failed to write file to disk. Introduced in PHP 5.1.0.';
                  break;
              default:
                  $response = 'Unknown error';
              break;
          }
          echo $response;
      }
    }
  }
}
