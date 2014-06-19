<? /*
<input id="fileupload" type="file" name="files[]" data-url="/publisher/post/upload" multiple>
<script src="/assets/packages/jquery-file-upload/js/vendor/jquery.ui.widget.js"></script>
<script src="/assets/packages/jquery-file-upload/js/jquery.iframe-transport.js"></script>
<script src="/assets/packages/jquery-file-upload/js/jquery.fileupload.js"></script>
<script>
    $(function () {
        $('#fileupload').fileupload({
            dataType: 'json',
            done: function (e, data) {
                $.each(data.result.files, function (index, file) {
                    $('<p/>').text(file.name).appendTo(document.body);
                });
            }
        });
    });
</script>
 */?>

<form action="/publisher/post/upload" method="post"
      enctype="multipart/form-data">
    <label for="file">Filename:</label>
    <input type="file" name="file" id="file"><br>
    <input type="file" name="file2" id="file2"><br>
    <input type="file" name="file3" id="file3"><br>
    <input type="file" name="file4" id="file4"><br>
    <input type="submit" name="submit" value="Submit">
</form>
