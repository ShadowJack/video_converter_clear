<?php 
echo "<table><thead>".
         "<td>Id</td>".
         "<td>Title</td>".
         "<td>flv</td>".
         "<td>mp4</td>".
         "<td>Info</td>".
         "<td>Delete link</td>".
     "</thead><tbody>";
foreach( $videos as $row ) 
{
    echo "<tr><td>".$row['id']."</td><td>".$row['title']."</td><td>";
    if ( ( $row['flv'] !== null ) && ( $row['flv'] !== '' ) )
    {
        echo "<a href='".$row['id']."/flv'>flv</a></td><td>";
    }
    else
    {
        echo "mp4</td><td>";
    }
    if ( ( $row['mp4'] !== null ) && ( $row['mp4'] !== '' ) )
    {
        echo "<a href='".$row['id']."/mp4'>mp4</a></td><td>";
    }
    else
    {
        echo "mp4</td><td>";
    }
    echo "<a href='".$row['id']."/meta'>info</a></td><td>".
         "<button onclick='deleteVideo(".$row['id'].")'>Delete</button></td></tr>";
}
echo "</tbody></table>";
echo "<a href='/video_converter/videos/new'>Add new video</a>"
?>