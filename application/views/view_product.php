<?php
$config['base_url'] = base_url( $this->config->item('index_page') . '/product/manage/' );
?>

<!-- Content Header (Page header) -->
<section class="content-header">
  <h1>
    Products
    <small>List</small>
  </h1>
  <ol class="breadcrumb">
    <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
    <li class="active">Products</li>
  </ol>
</section>

<!-- Main content -->

<section class="content">
  <div class="row">
    <div class="col-xs-12">
      <div class="box">
        <div class="box-header">
          <h3>Select csv to upload:</h3>
        </div><!-- /.box-header -->

        <div class="box-body">
          <form role="form" action="<?php echo $config['base_url']; ?>" method="post" enctype="multipart/form-data">
            <div class="box-body">
              <div class="form-group">
                <label for="exampleInputFile">File input</label>
                <input type="file" name="fileToUpload" id="fileToUpload">
                <p class="help-block">Only accept CSV Format</p>
              </div>
            </div><!-- /.box-body -->
            <div class="box-footer">
              <button type="submit" class="btn btn-primary">Submit</button>
            </div>
          </form>
        </div><!-- /.box-body -->
      </div><!-- /.box -->
    </div><!-- /.col -->
  </div><!-- /.row -->

<script>

$(document).ready(function(){

  var sync_page = 0;
  var sync_count = 0;

  // Editable
  $('.text').editable();

  // Sync Button Config
  $('.btn_sync').btn_init(
    'sync',
    { class : 'btn-warning', caption : 'Sync' },
    { class : 'btn-default fa fa-spinner', caption : '' },
    { class : 'btn-success', caption : 'Done' },
    { class : 'btn-danger', caption : 'Error' }
  );

  $('.btn_sync').click(function(){
    event.preventDefault();

    $(this).btn_action( 'sync', 'pending' );

    // Clear the sync value
    sync_page = 1;
    sync_count = 0;

    // Work with process
    funcSyncProcess();

  });

  var funcSyncProcess = function(){
    $.ajax({
      url: '<?php echo base_url($this->config->item('index_page') . '/product/sync') ?>' + '/' + $('#sel_shop').val() + '/' + sync_page,
      type: 'GET'
    }).done(function(data) {
      console.log( data );
      if( data == 'success' )
      {
        $('.btn_sync').btn_action( 'sync', 'success' );

        setTimeout( function(){
            window.location.reload();
          }, 1000
        );
      }
      else
      {
        var arr = data.split( '_' );

        sync_page = arr[0];
        sync_count = parseInt(sync_count) + parseInt(arr[1]);

        // Show the products
        $('.btn_sync').removeClass( 'fa fa-spinner');
        $('.btn_sync').html( sync_count + ' downloaded ...' );

        // Continue to access
        funcSyncProcess();
      }
    });
  }
});
</script>
