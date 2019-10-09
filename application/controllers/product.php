<?php //if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Product extends MY_Controller {

  public function __construct() {
    parent::__construct();
    //$this->load->model( 'Product_model' );
    ini_set('max_execution_time', 36000);
  }

  public function index(){
    $this->is_logged_in();
    $this->update_pos1();
  }

  public function manage( $page =  0 ){
    // Check the login
    $this->is_logged_in();

    $this->load->view('view_header');
    $this->load->view('view_product');
    $this->load->view('view_footer');
  }

  // function csv_to_array($filename='', $delimiter=',')
  // {
  // 	if(!file_exists($filename) || !is_readable($filename))
  // 		return FALSE;

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
      $this->Product_model->rewriteParam($this->config->item('PRIVATE_SHOP'));
      $shopify_products = $this->Product_model->getAll();
      $pos_products = $this->csv_to_array($this->config->item('app_path') . 'uploads/csv/' . $_GET[ "file_name" ]);

      $this->load->model( 'Shopify_model' );
      $this->_default_store = $this->config->item('PRIVATE_SHOP');
      $this->Shopify_model->setStore( $this->_default_store, $this->_arrStoreList[$this->_default_store]->app_id, $this->_arrStoreList[$this->_default_store]->app_secret );

      set_time_limit(0);

      $order = 0;

      foreach($shopify_products as $s_product)
      {
        $action = 'variants/' . $s_product->variant_id . '.json';

        $products_array = array(
                'variant' => array(
                    "id" => $s_product->variant_id,
                    "barcode" => $pos_products[$order]['UPC']
                )
        );

        // Retrive Data from Shop
        $productInfo = $this->Shopify_model->accessAPI( $action, $products_array, 'PUT' );
        $order = $order + 1;

        if(!isset($productInfo->variant)){
          var_dump("error" . '-' . $productInfo->errors->variant);
        }
        else{
          var_dump("success" . '-' . $productInfo->variant->sku);
        }
      }
      echo "POS Updated";
    }
  }

  public function update_pos1()
  {
    // Check the login
    $this->is_logged_in();

    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST");

    if(true)
    {
      $base_url = $this->config->item('base_url');
      $app_path = $this->config->item('app_path');

      //Import Product array from CSV
      $barcodes = $this->csv_to_array($this->config->item('app_path') . 'uploads/csv/' . 'barcodes.csv');
      $pos_products = $this->csv_to_array($this->config->item('app_path') . 'uploads/csv/' . 'inventory.csv');

      $this->load->model( 'Shopify_model' );
      $this->Shopify_model->setStore( $this->_default_store, $this->_arrStoreList[$this->_default_store]->app_id, $this->_arrStoreList[$this->_default_store]->app_secret );
      set_time_limit(0);
      //var_dump($pos['FULLPART']);exit;
      foreach($pos_products as $pos)
      {
        $temp_barcode = trim(preg_replace('/\s+/', ' ', $pos['FULLPART']));
        $import = true;
        foreach($barcodes as $barcode){
          if($barcode['1'] == $temp_barcode)
            {
              $import = false;
            }
        }
        if($import)
        {
          $action = 'products.json';

          $products_array = array(
              'product' => array(
                  "title" => trim(preg_replace('/\s+/', ' ', $pos['FULLPART'])) . ', ' . trim(preg_replace('/\s+/', ' ', $pos['MFR'])) . ', ' . trim(preg_replace('/\s+/', ' ', $pos['DESCRIPTION'])),
                  "body_html" => "<p>" . trim(preg_replace('/\s+/', ' ', $pos['DESCRIPTION'])) . "<\/p>",
                  "published" => 'true',
                  'variants' => array(
                    array(
                      "barcode" => $pos['FULLPART']
                    )
                  )
              )
          );
          // Retrive Data from Shop
          $update_productInfo = $this->Shopify_model->accessAPI( $action, $products_array, 'POST' );

          //var_dump($update_productInfo);exit;

          if(!isset($update_productInfo->product)){
            var_dump("error" . '-' .trim(preg_replace('/\s+/', ' ', $pos['FULLPART'])));
          }
          else{
            var_dump("success" . '-' . trim(preg_replace('/\s+/', ' ', $pos['FULLPART'])));
          }
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
