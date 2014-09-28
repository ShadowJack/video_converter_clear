<form enctype="multipart/form-data" action="/video_converter/videos/" method="POST">
    Title: <input type="text" name="title" /> <br />
    <input type="hidden" name="MAX_FILE_SIZE" value="40000000" /> <br />
    Video: <input name="newVideo" type="file" /> <br />
    <input type="submit" value="Send File" />
</form>
