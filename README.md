# class upload v1.1

To use this class, include class.upload.php to your php file. Default upload folder is '/files/uploads'. You can change this by passing a parameter to creator method ```$upload = new upload($new_dir);```

### methods

You can chain methods but you have to use `info()` method before others. 
info($files, $multiple) method takes two parameters:
- $files    : name of the file input
- $multiple : true if it is a multiple upload, false otherwise and default 

`ftype() : file extension controller`
- It takes unlimited parameters. Pass extensions you want to allow to upload as parameter.

`size() : file size controller`
- It takes only one parameter which is maximum allowed bytes. i.e 1048576 for 1MB
- default : `2097152` bytes

`name() : file name changer`
- It takes one parameter. If the parameter is null, it doesn't change the file name. Otherwise class changes file's name with as in parameter.
- If it is a multiple upload you can use `{id}` and `{time}` strings. Method will replace them with order of the file and unix timestamp. 

i.e parameter `image_upload_{id}` or `image_upload_{time}_{id}`

- If it is a singular upload you can't use `{id}`

`upload()`
You have to use this method to upload files and it must be last method on the chain.
Pass folder name as parameter if you want to upload a subfolder under main upload directory.
It returns array as result. If upload a single file successfully it returns

    array(
      "status"=> ok
      "file_name" => name of the file
      "file_extension"=> extension of file
      "file_size"=> size of the file as bytes
      "file_dir"=> dir of the file from root
    );
    
if single upload was not successfull it returns

    array(
      "status"=> error
      "errors"=> array of errors
    );

If upload multiple files successfully it returns

    array(
      "status"=> ok // it doesn't mean all files uploaded successfully
      "uploaded"=> number of the files successfully uploaded 
      "names"=> names of the files successfully uploaded
      "errors"=> error numbers of the files couldn't upload
    );
    
### errors
- 100 : extension unallowed
- 101 : unallowed size
- 102 : info() method not used
- 103 : file didn't uploaded completely to server
- 104 : move_uploaded_file() errors

### examples
This will upload selected file that has jpg, jpeg or png extension with any size to /files/uploads/images folder and rename it `uploaded_file.extension`

    $up  = new upload();
    $new = $up->info("image_input")->name("uploaded_image")->ftype("jpg", "jpeg", "png")->upload("images");

This will upload selected files that has png extension and less than 2097152 bytes to /files/uploads folder and rename them

    $up  = new upload();
    $new = $up->info("multiple_input", true)->name("uploaded_image_{id}")->ftype("png")->size(2097152)->upload();
