// we use this function to make DELETE /videos/:id request
function deleteVideo(id)
{
    $.ajax({
        type: "DELETE",
        url: ""+id,
        success: function(msg)
        {
            window.location.href = "";
        },
        error: function(msg)
        {
            console.log(msg);
        }
    });
}
