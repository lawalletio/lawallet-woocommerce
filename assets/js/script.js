(function ($) {
  const ajax_url = './admin-ajax.php';
  // ON start
  $(() => {
    $('#mainform input[type=file]').change(onUploadChange);
  });

  function onUploadChange() {
    upload_file(this);
  }


  function upload_file(fileHandler) {
    var fd = new FormData();
    var files = $(fileHandler)[0].files[0];

    fd.append('file', files);
    fd.append('action', 'ln_upload_file');
    fd.append('name', fileHandler.name);

    $.ajax({
        url: ajax_url,
        type: 'post',
        data: fd,
        contentType: false,
        processData: false,
        success: function(response){
          $('#uploaded_label_' + fileHandler.name).removeClass('hidden');
        },
        fail: res => {
          alert(res.data);
          throw new Error('unexpected status code '+res.status)
        }
    });
  }


})(jQuery);
