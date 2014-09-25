<table>
    <thead>
        <td>Title</td>
        <td>Dimensions</td>
        <td>Video bitrate</td>
        <td>Audio bitrate</td>
    </thead>
    <tbody>
        <?php
        foreach( $meta as $elem )
        {
            echo "<td>$elem</td>";
        }
        ?>
    </tbody>
<table>