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
          <form role="form" id="submit_form" action="" method="post" enctype="multipart/form-data">
            <div class="box-body">
              <div class="form-group">
                <label for="InputTag">Tag</label>
                <input type="text" class="form-control" style="max-width: 50%;" id="InputTag" value="shopify_pos" disabled placeholder="POS Product Tag">
              </div>
              <div class="form-group">
                <label for="exampleInputFile">File input</label>
                <input type="file" name="file" id="fileToUpload">
                <p class="help-block">Only accept CSV Format</p>
              </div>
            </div><!-- /.box-body -->
            <div class="box-footer">
              <input type="submit" id="upload_button" class="btn btn-primary" value="Upload">
              <input id="update_pos" class="btn btn-warning" style="display: none;" value="Update POS Products">
              <input id="update_pos_file" class="hidden" value="">
            </div>
          </form>
        </div><!-- /.box-body -->
      </div><!-- /.box -->
    </div><!-- /.col -->
  </div><!-- /.row -->

<script>

$(document).ready(function(){
  $("#submit_form").on('submit',(function(e) {
    e.preventDefault();
    $.ajax({
           url: "<?PHP echo base_url(); ?>product/upload_csv",
           type: "POST",
           data:  new FormData(this),
           contentType: false,
           cache: false,
           processData:false,
           success: function(data)
                {
                  if(data.search('.csv') == -1 ){
                      alert(data);
                  }else{
                      $("#update_pos").addClass('btn-warning');
                      $("#update_pos").removeClass('btn-success');
                      $("#update_pos").val("Update POS Products");
                      $('#update_pos').show();
                      $('#update_pos_file').val(data);
                    }
                },
            error: function(e)
                {

                }
      });
   }));

   $("#update_pos").on('click',(function(e) {
     var file_name = $('#update_pos_file').val();
     var pos_tag = $('#InputTag').val();
     $("#update_pos").val("POS Updating...");
     $.ajax({
            url: "<?PHP echo base_url(); ?>product/update_pos?file_name=" + file_name + '&pos_tag=' + pos_tag,
            type: "GET",
            success: function(data)
                 {
                   $("#update_pos").removeClass('btn-warning');
                   $("#update_pos").addClass('btn-success');
                   $("#update_pos").val("Successfully Updated");
                 },
             error: function(e)
                 {
                   alert(data);
                 }
       });
    }));
});
</script>
