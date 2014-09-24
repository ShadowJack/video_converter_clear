<?php 
echo "<table><thead>".
         "<td>Id</td>".
         "<td>Title</td>".
         "<td>FLV</td>".
         "<td>MP4</td>".
         "<td>Dimensions</td>".
         "<td>Video bitrate</td>".
         "<td>Audio bitrate</td>".
         "<td>Delete link</td>".
     "</thead><tbody>";
foreach($videos as $row) {
echo "<tr><td>".$row['id']."</td><td>".
                $row['title']."</td><td>".
                $row['FLV']."</td><td>".
                $row['MP4']."</td><td>".
                $row['dimensions']."</td><td>".
                $row['bv']."</td><td>".
                $row['ba']."</td><td>".
                "<button onclick='deleteVideo(".$row['id'].")'>Delete</button></td></tr>";
}
echo "</tbody></table>";
echo "<a href='new'>Add new video</a>"
?>