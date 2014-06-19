<!-- Load Google JSAPI -->
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript">
    google.load("visualization", "1", { packages: ["corechart"] });
    google.setOnLoadCallback(drawChart);


    function drawChart() {
        var data = new google.visualization.DataTable();

        data.addColumn('datetime', 'Date');
        <?php
        $day_ago = strtotime("-3 days", time());
                    foreach($methods as $m => $v){
                $method = $v['method'];
                ?>

        data.addColumn('number', '<?=$method;?>');

        <?php
        }
        ?>

        data.addColumn('number', 'Total');

        <?php
                    foreach($calls as $date => $v){

                $datetime       = $date;
                $chart_time_js  = $datetime * 1000;
                $total          = $calls[$datetime]['total'];

                if($date >= $day_ago){

                    echo "var date = new Date($chart_time_js);";
                    echo "data.addRow([date";

                    foreach($methods as $m => $v){

                        $method = $v['method'];

                        if(isset($calls[$datetime][$method]['calls'])){
                            $call_count = $calls[$datetime][$method]['calls'];
                        } else {
                            $call_count = 0;
                        }

                        echo ", $call_count";

                    }


                    echo ", $total";
                    echo "]);
                    ";
                }
            }
            ?>

        var options = {
            title: 'Pinterest API Calls last 72 hrs',
            isStacked: true,
            legend: {position:'right'},
            chartArea: {width:'70%', left:100, top:100},
            focusTarget: 'category',
            series: {16:{areaOpacity:0, color: 'transparent'}},
            vAxis: {viewWindow: {max: '85000'}}
        };

        var chart = new google.visualization.AreaChart(
            document.getElementById('chart_div'));
        chart.draw(data, options);
    }

</script>

<div id="chart_div" style="width: 100%; height: 900px;"></div>


<script type="text/javascript">
    google.load("visualization", "1", { packages: ["corechart"] });
    google.setOnLoadCallback(drawChart);


    function drawChart() {
        var data = new google.visualization.DataTable();

        data.addColumn('datetime', 'Date');
        <?php
        $day_ago = strtotime("-3 days", time());
                    foreach($methods_alt as $m => $v){
                $method = $v['method'];
                ?>

        data.addColumn('number', '<?=$method;?>');

        <?php
        }
        ?>

        data.addColumn('number', 'Total');

        <?php
            foreach($calls_alt as $date => $v){

                $datetime       = $date;
                $chart_time_js  = $datetime * 1000;
                $total          = $calls_alt[$datetime]['total'];

                if($date >= $day_ago){

                    echo "var date = new Date($chart_time_js);";
                    echo "data.addRow([date";

                    foreach($methods_alt as $m => $v){

                        $method = $v['method'];

                        if(isset($calls_alt[$datetime][$method]['calls'])){
                            $call_count = $calls_alt[$datetime][$method]['calls'];
                        } else {
                            $call_count = 0;
                        }

                        echo ", $call_count";

                    }


                    echo ", $total";
                    echo "]);
                    ";
                }
            }
            ?>

        var options = {
            title: 'Pinterest API Calls last 72 hrs - NEW CLIENT ID',
            isStacked: true,
            legend: {position:'right'},
            chartArea: {width:'70%', left:100, top:100},
            focusTarget: 'category',
            series: {16:{areaOpacity:0, color: 'transparent'}},
            vAxis: {viewWindow: {max: '85000'}}
        };

        var chart = new google.visualization.AreaChart(
            document.getElementById('chart_div2'));
        chart.draw(data, options);
    }

</script>

<div id="chart_div2" style="width: 100%; height: 900px;"></div>