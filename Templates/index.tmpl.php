<?php 
echo "<table><thead>".
         "<td>Id</td>".
         "<td>Title</td>".
         "<td>FLV</td>".
         "<td>MP4</td>".
         "<td>Info</td>".
         "<td>Delete link</td>".
     "</thead><tbody>";
foreach( $videos as $row ) 
{
    echo "<tr><td>".$row['id']."</td><td>".$row['title']."</td><td>";
    if ( ( $row['FLV'] !== null ) && ( $row['FLV'] !== '' ) )
    {
        echo "<a href='".$row['id']."/flv'>FLV</a></td><td>";
    }
    else
    {
        echo "FLV</td><td>";
    }
    if ( ( $row['MP4'] !== null ) && ( $row['MP4'] !== '' ) )
    {
        echo "<a href='".$row['id']."/mp4'>MP4</a></td><td>";
    }
    else
    {
        echo "MP4</td><td>";
    }
    echo "<a href='".$row['id']."/meta'>info</a></td><td>".
         "<button onclick='deleteVideo(".$row['id'].")'>Delete</button></td></tr>";
}
echo "</tbody></table>";
echo "<a href='/video_converter/videos/new'>Add new video</a>"
?>