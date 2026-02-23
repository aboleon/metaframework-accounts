<script>

  function init_charts() {

    console.log('run_charts  typeof [' + typeof (Chart) + ']');

    if( typeof (Chart) === 'undefined'){ return; }

    console.log('init_charts');


    Chart.defaults.global.legend = {
      enabled: false
  };

// Doughnut chart

if ($('#canvasDoughnut').length ){

 var ctx = document.getElementById("canvasDoughnut");
 var data = {
  labels: [
  "Dark Grey",
  "Purple Color",
  "Gray Color",
  "Green Color",
  "Blue Color"
  ],
  datasets: [{
    data: [120, 50, 140, 180, 100],
    backgroundColor: [
    "#455C73",
    "#9B59B6",
    "#BDC3C7",
    "#26B99A",
    "#3498DB"
    ],
    hoverBackgroundColor: [
    "#34495E",
    "#B370CF",
    "#CFD4D8",
    "#36CAAB",
    "#49A9EA"
    ]

}]
};

var canvasDoughnut = new Chart(ctx, {
  type: 'doughnut',
  tooltipFillColor: "rgba(51, 51, 51, 0.55)",
  data: data
});

}


}



function init_chart_doughnut(){

  if( typeof (Chart) === 'undefined'){ return; }

  console.log('init_chart_doughnut');

if ($('.canvasDoughnut').length){

    var chart_doughnut_settings = {
      type: 'doughnut',
      tooltipFillColor: "rgba(51, 51, 51, 0.55)",
      data: {
        labels: [
        @foreach($clients['bygroup'] as $key=>$virgo)
        "{!! array_key_exists($key, $clients['named_countries']) ? $clients['named_countries'][$key] : __('mfw-accounts::ui.country_unknown') !!}",
        @endforeach
        ],
        datasets: [{
            data: [
            @foreach($clients['bygroup'] as $key=>$virgo)
            {{ $virgo }},
            @endforeach
            ],
            backgroundColor: [
            @foreach($backgroundColor as $virgo)
            "{!! $virgo !!}",
            @endforeach
            ],
            hoverBackgroundColor: [
            @foreach($hoverBackgroundColor as $virgo)
            "{!! $virgo !!}",
            @endforeach
            ]
        }]
    },
    options: {
       legend: false,
       responsive: false
   }
}

$('.canvasDoughnut').each(function(){

    var chart_element = $(this);
    var chart_doughnut = new Chart( chart_element, chart_doughnut_settings);

});

}

}

init_charts();
init_chart_doughnut();

</script>


